<?php

namespace Modules\Members\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Modules\Members\Models\members;

class MembersController extends Controller
{
    private function checkPermission(string $permission)
    {
        $user = Auth::user();
        if ($user && method_exists($user, 'hasPermission')) {
            if (!$user->hasPermission($permission)) {
                abort(403, "Action non autorisée : permission '{$permission}' requise.");
            }
            return;
        }

        if ($user && $user->role === 'tresorier') {
            abort(403, 'Action non autorisée pour le trésorier.');
        }
    }

    private function checkGestionnaire()
    {
        $this->checkPermission('creer_membre');
    }

    /**
     * Liste des membres (Image 03)
     */
    public function index(Request $request)
    {
        $query = members::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('prenom', 'like', "%{$search}%")
                  ->orWhere('telephone', 'like', "%{$search}%");
            });
        }

        if ($statut = $request->input('statut')) {
            if ($statut !== 'tous') {
                $query->where('statut', $statut);
            }
        }

        $membres = $query->get();

        return Inertia::render('Membres/Index', [
            'membres' => $membres,
            'filters' => [
                'search' => $search,
                'statut' => $statut ?? 'tous',
            ],
        ]);
    }

    /**
     * Formulaire création d'un membre (Image 04)
     */
    public function create()
    {
        $this->checkGestionnaire();

        return Inertia::render('Membres/Create');
    }

    /**
     * Enregistrer un nouveau membre
     */
    public function store(Request $request)
    {
        $this->checkGestionnaire();

        $data = $request->validate([
            'nom'           => 'required|string',
            'prenom'        => 'required|string',
            'email'         => 'nullable|email|unique:members,email',
            'telephone'     => 'required|unique:members,telephone',
            'adresse'       => 'required|string',
            'date_adhesion' => 'required|date',
            'statut'        => 'required|in:actif,desactive'
        ]);

        $member = members::create($data);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'member'  => $member,
                'message' => 'Membre créé avec succès.'
            ]);
        }

        return redirect()->route('members.index')->with('success', 'Membre créé avec succès.');
    }

    /**
     * Afficher ou éditer un membre avec les anciennes valeurs pré-remplies
     */
    public function edit(members $member)
    {
        $this->checkPermission('modifier_membre');

        return Inertia::render('Membres/Edit', [
            'member' => $member
        ]);
    }

    public function show(members $member)
    {
        return $this->edit($member);
    }

    /**
     * Mettre à jour un membre
     */
    public function update(Request $request, members $member)
    {
        $this->checkPermission('modifier_membre');

        $data = $request->validate([
            'nom'           => 'required|string',
            'prenom'        => 'required|string',
            'telephone'     => 'required|unique:members,telephone,' . $member->id,
            'email'         => 'nullable|email|unique:members,email,' . $member->id,
            'adresse'       => 'required|string',
            'date_adhesion' => 'required|date',
            'statut'        => 'required|in:actif,desactive'
        ]);

        $member->update($data);

        return redirect()->route('members.index')->with('success', 'Membre modifié avec succès.');
    }

    /**
     * Supprimer un membre
     */
    public function destroy(members $member)
    {
        $this->checkPermission('supprimer_membre');

        $member->delete();

        return redirect()->route('members.index')->with('success', 'Membre supprimé avec succès.');
    }

    /**
     * Raccourci pour basculer le statut d'un membre (actif <-> desactive) sans ouvrir le formulaire
     */
    public function toggleStatut(members $member)
    {
        $this->checkPermission('modifier_membre');

        $nouveauStatut = $member->statut === 'actif' ? 'desactive' : 'actif';
        $member->update(['statut' => $nouveauStatut]);

        $message = $nouveauStatut === 'desactive' ? 'Membre désactivé avec succès.' : 'Membre réactivé avec succès.';
        return redirect()->back()->with('success', $message);
    }

    /**
     * Importer des membres depuis un fichier Excel (.xlsx, .xls) ou CSV
     */
    public function import(Request $request)
    {
        $this->checkPermission('creer_membre');

        $request->validate([
            'fichier' => 'required|file|mimes:xlsx,xls,csv,txt|max:5120',
        ]);

        $file = $request->file('fichier');
        $extension = strtolower($file->getClientOriginalExtension());
        $path = $file->getRealPath();

        $rows = [];

        // Helper pour convertir une lettre de colonne Excel (A, B, C, ... AA) en index 0-based
        $colLetterToIndex = function ($cellRef) {
            $colLetters = preg_replace('/[0-9]/', '', (string)$cellRef);
            $colLetters = strtoupper($colLetters);
            $len = strlen($colLetters);
            $index = 0;
            for ($i = 0; $i < $len; $i++) {
                $index = $index * 26 + (ord($colLetters[$i]) - ord('A') + 1);
            }
            return max(0, $index - 1);
        };

        if ($extension === 'csv' || $extension === 'txt') {
            // Lecture du fichier CSV
            if (($handle = fopen($path, 'r')) !== false) {
                // Détecter délimiteur (, ou ;)
                $firstLine = fgets($handle);
                $delimiter = strpos($firstLine, ';') !== false ? ';' : ',';
                rewind($handle);

                while (($data = fgetcsv($handle, 1000, $delimiter)) !== false) {
                    $rows[] = array_map('trim', $data);
                }
                fclose($handle);
            }
        } elseif ($extension === 'xlsx' && class_exists('ZipArchive')) {
            // Parser XML direct pour .xlsx
            $zip = new \ZipArchive();
            if ($zip->open($path) === true) {
                // 1. Lire sharedStrings
                $strings = [];
                if ($xmlStrings = $zip->getFromName('xl/sharedStrings.xml')) {
                    $sXml = simplexml_load_string($xmlStrings);
                    foreach ($sXml->si as $val) {
                        $strings[] = (string) ($val->t ?? ($val->r ? $val->r->t : ''));
                    }
                }

                // 2. Lire la première feuille sheet1.xml
                if ($sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml')) {
                    $sXml = simplexml_load_string($sheetXml);
                    foreach ($sXml->sheetData->row as $r) {
                        $row = [];
                        $maxIdx = 0;
                        foreach ($r->c as $c) {
                            $cellRef = (string) $c['r'];
                            $colIdx = $colLetterToIndex($cellRef);
                            if ($colIdx > $maxIdx) {
                                $maxIdx = $colIdx;
                            }

                            $type = (string) $c['t'];
                            $val = (string) $c->v;
                            if ($type === 's' && isset($strings[(int) $val])) {
                                $val = $strings[(int) $val];
                            } elseif ($type === 'inlineStr' && isset($c->is->t)) {
                                $val = (string)$c->is->t;
                            }
                            $row[$colIdx] = trim($val);
                        }

                        // Combler les cellules vides intermédiaires pour préserver les positions de colonnes
                        $normalizedRow = [];
                        for ($k = 0; $k <= $maxIdx; $k++) {
                            $normalizedRow[$k] = $row[$k] ?? '';
                        }

                        if (!empty(array_filter($normalizedRow, fn($v) => $v !== ''))) {
                            $rows[] = $normalizedRow;
                        }
                    }
                }
                $zip->close();
            }
        }

        if (count($rows) <= 1) {
            return redirect()->back()->with('error', 'Le fichier ne contient aucune donnée ou est vide.');
        }

        // Helpers de conversion
        $parseDate = function ($value) {
            if (!$value) return now()->toDateString();
            $value = trim($value);

            // Numéro de série de date Excel (ex: 45248)
            if (is_numeric($value) && (int)$value > 1000 && (int)$value < 90000) {
                return \Carbon\Carbon::create(1899, 12, 30)->addDays((int)$value)->toDateString();
            }

            // Formats textuels courants (DD/MM/YYYY, YYYY-MM-DD, etc.)
            $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d', 'd.m.Y', 'm/d/Y'];
            foreach ($formats as $f) {
                try {
                    $parsed = \Carbon\Carbon::createFromFormat($f, $value);
                    if ($parsed && $parsed->format($f) === $value) {
                        return $parsed->toDateString();
                    }
                } catch (\Throwable $e) {}
            }

            try {
                return \Carbon\Carbon::parse($value)->toDateString();
            } catch (\Throwable $e) {
                return now()->toDateString();
            }
        };

        $normalizePhone = function ($tel) {
            if (!$tel) return null;
            $clean = preg_replace('/[^\d+]/', '', (string)$tel);

            // Supprimer le préfixe pays +225 ou 225
            if (str_starts_with($clean, '+225')) {
                $clean = substr($clean, 4);
            } elseif (str_starts_with($clean, '225') && strlen($clean) > 10) {
                $clean = substr($clean, 3);
            }

            // Si 9 chiffres et ne commence pas par 0 (Excel a tronqué le 0 initial)
            if (strlen($clean) === 9 && !str_starts_with($clean, '0')) {
                $clean = '0' . $clean;
            } elseif (strlen($clean) === 7 && !str_starts_with($clean, '0')) {
                $clean = '0' . $clean;
            }

            return $clean;
        };

        // Ligne 1 = En-têtes normalisés
        $headers = array_map(function ($h) {
            $h = strtolower(trim((string)$h));
            // Retirer les accents
            return iconv('UTF-8', 'ASCII//TRANSLIT', $h) ?: $h;
        }, $rows[0]);

        $findCol = function (array $patterns, array $headers) {
            foreach ($headers as $idx => $header) {
                foreach ($patterns as $pattern) {
                    if (str_contains($header, $pattern)) {
                        return $idx;
                    }
                }
            }
            return false;
        };

        $nomIdx       = $findCol(['nom'], $headers);
        $prenomIdx    = $findCol(['prenom'], $headers);
        $telIdx       = $findCol(['tel', 'contact', 'numero', 'cellulaire', 'phone'], $headers);
        $emailIdx     = $findCol(['email', 'mail', 'courriel'], $headers);
        $adresseIdx   = $findCol(['adresse', 'domicile', 'ville', 'commune'], $headers);
        $dateAdhIdx   = $findCol(['adhesion', 'date'], $headers);
        $statutIdx    = $findCol(['statut', 'etat'], $headers);

        if ($nomIdx === false || $prenomIdx === false || $telIdx === false) {
            // Si pas d'en-tête explicite détecté, assumer l'ordre par colonnes par défaut:
            // 0: nom, 1: prenom, 2: telephone, 3: email, 4: adresse, 5: date_adhesion, 6: statut
            $nomIdx = 0; $prenomIdx = 1; $telIdx = 2; $emailIdx = 3; $adresseIdx = 4; $dateAdhIdx = 5; $statutIdx = 6;
        }

        $imported = 0;
        $ignored = 0;

        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            if (empty(array_filter($row, fn($v) => $v !== '' && $v !== null))) continue;

            $nom       = isset($row[$nomIdx]) ? trim((string)$row[$nomIdx]) : null;
            $prenom    = isset($row[$prenomIdx]) ? trim((string)$row[$prenomIdx]) : null;
            $telRaw    = isset($row[$telIdx]) ? trim((string)$row[$telIdx]) : null;
            $telephone = $normalizePhone($telRaw);

            $email     = ($emailIdx !== false && isset($row[$emailIdx]) && trim((string)$row[$emailIdx]) !== '') ? trim((string)$row[$emailIdx]) : null;
            $adresse   = ($adresseIdx !== false && isset($row[$adresseIdx]) && trim((string)$row[$adresseIdx]) !== '') ? trim((string)$row[$adresseIdx]) : 'Non renseignée';
            $dateAdh   = ($dateAdhIdx !== false && isset($row[$dateAdhIdx])) ? $parseDate($row[$dateAdhIdx]) : now()->toDateString();
            
            $statutVal = ($statutIdx !== false && isset($row[$statutIdx])) ? strtolower(trim((string)$row[$statutIdx])) : 'actif';
            $statut    = in_array($statutVal, ['actif', 'desactive']) ? $statutVal : 'actif';

            if (!$nom || !$prenom || !$telephone) {
                $ignored++;
                continue;
            }

            // Éviter les doublons par téléphone
            $exists = members::where('telephone', $telephone)->exists();
            if ($exists) {
                $ignored++;
                continue;
            }

            members::create([
                'nom'           => $nom,
                'prenom'        => $prenom,
                'telephone'     => $telephone,
                'email'         => $email,
                'adresse'       => $adresse,
                'date_adhesion' => $dateAdh,
                'statut'        => $statut,
            ]);
            $imported++;
        }

        return redirect()->back()->with('success', "Importation réussie : {$imported} membre(s) créé(s), {$ignored} ignoré(s) ou doublon(s).");
    }
}
