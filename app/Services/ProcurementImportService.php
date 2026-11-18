<?php  
  
namespace App\Services;  
  
use App\Models\Article;  
use App\Models\consultation;  
use App\Models\demande;  
use App\Models\fournisseurs;  
use App\Models\offre;  
use Carbon\Carbon;  
use App\Models\Procesverbal;
use Illuminate\Support\Facades\DB;  
use PhpOffice\PhpSpreadsheet\IOFactory;  
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;  
use Throwable;  
  
class ProcurementImportService  
{  
    /*  
    |--------------------------------------------------------------------------  
    | TYPES D'IMPORT  
    |--------------------------------------------------------------------------  
    */  
  
    public const TYPES = [  
        'consultation' => 'Consultation',  
        'pv' => 'Procès-verbal',  
        'repartition' => 'Répartition',  
    ];  
  
    /*  
    |--------------------------------------------------------------------------  
    | RÉCUPÉRER LES FEUILLES EXCEL  
    |--------------------------------------------------------------------------  
    */  
  
    public function getExcelSheets(string $path): array  
    {  
        if (!is_string($path) || trim($path) === '') {  
            throw new \RuntimeException(  
                'Le chemin du fichier Excel est vide.'  
            );  
        }  
  
        if (!file_exists($path)) {  
            throw new \RuntimeException(  
                "Le fichier Excel est introuvable : {$path}"  
            );  
        }  
  
        $spreadsheet = IOFactory::load($path);  
  
        $sheets = [];  
  
        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {  
            $title = trim($sheet->getTitle());  
  
            if ($title !== '') {  
                $sheets[] = $title;  
            }  
        }  
  
        return $sheets;  
    }  
  
    /*  
    |--------------------------------------------------------------------------  
    | PREVIEW  
    |--------------------------------------------------------------------------  
    */  
  
    public function preview(  
        string $path,  
        string $type,  
        string $service,  
        ?string $sheetName = null  
    ): array {  
        $parsed = $this->parse(  
            $path,  
            $sheetName,  
            $type  
        );  
  
        return [  
            'type' => $type,  
            'service' => $service,  
            'reference' => $parsed['reference'] ?? null,  
            'objet' => $parsed['objet'] ?? null,  
            'date_lancement' => $parsed['date_lancement'] ?? null,  
            'date_limite' => $parsed['date_limite'] ?? null,  
            'articles' => $parsed['articles'] ?? [],  
            'fournisseurs' => $parsed['fournisseurs'] ?? [],  
            'offers' => $parsed['offers'] ?? [],  
            'errors' => $parsed['errors'] ?? [],  
            'warnings' => $parsed['warnings'] ?? [],  
            'sheet' => $sheetName,  
        ];  
    }  
  
    /*  
    |--------------------------------------------------------------------------  
    | IMPORT DANS LA BASE  
    |--------------------------------------------------------------------------  
    */  
public function import(
    array $summary,
    string $service
): int {

    return DB::transaction(
        function () use ($summary, $service) {

            /*
            |--------------------------------------------------------------------------
            | TYPE D'IMPORT
            |--------------------------------------------------------------------------
            */

            $type = $summary['type'] ?? '';

            /*
            |--------------------------------------------------------------------------
            | RÉFÉRENCE
            |--------------------------------------------------------------------------
            */

            $reference = $this->normalizeReference(
                $summary['reference'] ?? ''
            );
            // Vérifier si la consultation existe déjà
if (
    $type === 'consultation' &&
    consultation::where('reference', $reference)->exists()
) {
    throw new \RuntimeException(
        "Importation impossible : la consultation « {$reference} » existe déjà."
    );
}

            /*
            |--------------------------------------------------------------------------
            | POUR PV / RÉPARTITION :
            | la consultation doit déjà exister
            |--------------------------------------------------------------------------
            */

            if (
                $type === 'pv' ||
                $type === 'repartition'
            ) {

                $consultationExistante =
                    consultation::where(
                        'reference',
                        $reference
                    )->first();

                if (!$consultationExistante) {

                    throw new \RuntimeException(
                        "Import bloqué : la consultation « {$reference} » "
                        . "n’existe pas dans le système. "
                        . "Vérifiez la référence du fichier Excel."
                    );
                }
            }

            /*
            |--------------------------------------------------------------------------
            | OBJET
            |--------------------------------------------------------------------------
            */

            $objet = trim(
                (string) (
                    $summary['objet'] ?? ''
                )
            );

            /*
            |--------------------------------------------------------------------------
            | DEMANDE
            |--------------------------------------------------------------------------
            */

            $demandeModel =
                demande::firstOrCreate(
                    [
                        'reference' =>
                            $reference,
                    ],
                    [
                        'objet' =>
                            $objet !== ''
                                ? $objet
                                : 'Objet importé',

                        'service_demandeur' =>
                            $service,

                        'date_demande' =>
                            now(),
                    ]
                );

            /*
            |--------------------------------------------------------------------------
            | METTRE À JOUR L'OBJET SI NÉCESSAIRE
            |--------------------------------------------------------------------------
            */

            if (
                $objet !== '' &&
                trim(
                    (string)
                    $demandeModel->objet
                ) === ''
            ) {

                $demandeModel->objet =
                    $objet;

                $demandeModel->save();
            }

            /*
            |--------------------------------------------------------------------------
            | CONSULTATION
            |--------------------------------------------------------------------------
            */

            $consultationModel =
                consultation::firstOrNew(
                    [
                        'reference' =>
                            $reference,
                    ]
                );

            $consultationModel
                ->demande_achat_id =
                $demandeModel->id;

            if ($objet !== '') {

                $consultationModel->objet =
                    $objet;

            } elseif (
                trim(
                    (string)
                    $consultationModel->objet
                ) === ''
            ) {

                $consultationModel->objet =
                    $demandeModel->objet;
            }

            /*
            |--------------------------------------------------------------------------
            | DATES
            |--------------------------------------------------------------------------
            |
            | PV et répartition :
            | on conserve les dates déjà présentes.
            |--------------------------------------------------------------------------
            */

            if (
                $type !== 'pv' &&
                $type !== 'repartition'
            ) {

                $consultationModel
                    ->date_lancement =
                    $summary['date_lancement']
                    ?? null;

                $consultationModel
                    ->date_limite =
                    $summary['date_limite']
                    ?? null;
            }

            $consultationModel->save();

            /*
            |--------------------------------------------------------------------------
            | ARTICLES
            |--------------------------------------------------------------------------
            */

            $articleIds = [];

            /*
            |--------------------------------------------------------------------------
            | ARTICLES EXISTANTS DE LA CONSULTATION
            |--------------------------------------------------------------------------
            |
            | Pour PV et répartition, on charge les articles existants.
            |--------------------------------------------------------------------------
            */

            $existingArticles = collect();

            if (
                $type === 'pv' ||
                $type === 'repartition'
            ) {

                $existingArticles =
                    Article::where(
                        'consultation_id',
                        $consultationModel->id
                    )
                    ->orderBy('id')
                    ->get();
            }

            /*
            |--------------------------------------------------------------------------
            | ARTICLES DÉJÀ UTILISÉS
            |--------------------------------------------------------------------------
            |
            | Important lorsqu'il existe plusieurs articles
            | avec la même désignation.
            |--------------------------------------------------------------------------
            */

            $usedArticleIds = [];

            /*
            |--------------------------------------------------------------------------
            | PARCOURIR LES ARTICLES DU FICHIER
            |--------------------------------------------------------------------------
            */

            foreach (
                ($summary['articles'] ?? [])
                as $articleIndex => $articleData
            ) {

                $designation = trim(
                    (string) (
                        $articleData['designation']
                        ?? $articleData['article']
                        ?? ''
                    )
                );

                if ($designation === '') {
                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | DESCRIPTION
                |--------------------------------------------------------------------------
                */

                $description =
                    $articleData['description']
                    ?? null;

                if (
                    $description !== null &&
                    trim(
                        (string) $description
                    ) === ''
                ) {

                    $description = null;
                }

                /*
                |--------------------------------------------------------------------------
                | QUANTITÉ
                |--------------------------------------------------------------------------
                */

                $quantite =
                    $articleData['quantite']
                    ?? $articleData['quantity']
                    ?? null;

                /*
                |--------------------------------------------------------------------------
                | CAS PV
                |--------------------------------------------------------------------------
                |
                | Pour un PV :
                | on réutilise uniquement un article existant.
                | Aucun article n'est créé.
                |--------------------------------------------------------------------------
                */

                if ($type === 'pv') {

                    $matchedArticle = null;

                    $normalizedDesignation =
                        mb_strtolower(
                            preg_replace(
                                '/\s+/u',
                                ' ',
                                trim($designation)
                            ),
                            'UTF-8'
                        );

                    /*
                    |--------------------------------------------------------------------------
                    | 1. RECHERCHE : désignation + quantité
                    |--------------------------------------------------------------------------
                    */

                    foreach (
                        $existingArticles
                        as $existingArticle
                    ) {

                        if (
                            in_array(
                                $existingArticle->id,
                                $usedArticleIds,
                                true
                            )
                        ) {
                            continue;
                        }

                        $existingDesignation =
                            mb_strtolower(
                                preg_replace(
                                    '/\s+/u',
                                    ' ',
                                    trim(
                                        (string)
                                        $existingArticle->designation
                                    )
                                ),
                                'UTF-8'
                            );

                        if (
                            $existingDesignation !==
                            $normalizedDesignation
                        ) {
                            continue;
                        }

                        $sameQuantity = true;

                        if (
                            $quantite !== null &&
                            $existingArticle->quantite !== null
                        ) {

                            $sameQuantity =
                                (float) $quantite ===
                                (float)
                                $existingArticle->quantite;
                        }

                        if ($sameQuantity) {

                            $matchedArticle =
                                $existingArticle;

                            break;
                        }
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | 2. FALLBACK : désignation seulement
                    |--------------------------------------------------------------------------
                    */

                    if (!$matchedArticle) {

                        foreach (
                            $existingArticles
                            as $existingArticle
                        ) {

                            if (
                                in_array(
                                    $existingArticle->id,
                                    $usedArticleIds,
                                    true
                                )
                            ) {
                                continue;
                            }

                            $existingDesignation =
                                mb_strtolower(
                                    preg_replace(
                                        '/\s+/u',
                                        ' ',
                                        trim(
                                            (string)
                                            $existingArticle->designation
                                        )
                                    ),
                                    'UTF-8'
                                );

                            if (
                                $existingDesignation ===
                                $normalizedDesignation
                            ) {

                                $matchedArticle =
                                    $existingArticle;

                                break;
                            }
                        }
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | ARTICLE TROUVÉ
                    |--------------------------------------------------------------------------
                    */

                    if ($matchedArticle) {

                        $usedArticleIds[] =
                            $matchedArticle->id;

                        $articleIds[
                            $articleIndex
                        ] =
                            $matchedArticle->id;

                        /*
                        | Compléter la description si nécessaire.
                        */

                        if (
                            $description !== null &&
                            trim(
                                (string)
                                $description
                            ) !== ''
                        ) {

                            $matchedArticle->description =
                                trim(
                                    (string)
                                    $description
                                );

                            $matchedArticle->save();
                        }
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | IMPORTANT :
                    | aucun article nouveau pour le PV.
                    |--------------------------------------------------------------------------
                    */

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | CAS RÉPARTITION
                |--------------------------------------------------------------------------
                |
                | 1. Chercher dans les articles existants.
                | 2. S'il existe -> réutiliser.
                | 3. Sinon -> créer l'article.
                |--------------------------------------------------------------------------
                */

                if ($type === 'repartition') {

                    $matchedArticle = null;

                    $normalizedDesignation =
                        mb_strtolower(
                            preg_replace(
                                '/\s+/u',
                                ' ',
                                trim($designation)
                            ),
                            'UTF-8'
                        );

                    /*
                    |--------------------------------------------------------------------------
                    | 1. RECHERCHE : désignation + quantité
                    |--------------------------------------------------------------------------
                    */

                    foreach (
                        $existingArticles
                        as $existingArticle
                    ) {

                        if (
                            in_array(
                                $existingArticle->id,
                                $usedArticleIds,
                                true
                            )
                        ) {
                            continue;
                        }

                        $existingDesignation =
                            mb_strtolower(
                                preg_replace(
                                    '/\s+/u',
                                    ' ',
                                    trim(
                                        (string)
                                        $existingArticle->designation
                                    )
                                ),
                                'UTF-8'
                            );

                        if (
                            $existingDesignation !==
                            $normalizedDesignation
                        ) {
                            continue;
                        }

                        $sameQuantity = true;

                        if (
                            $quantite !== null &&
                            $existingArticle->quantite !== null
                        ) {

                            $sameQuantity =
                                (float) $quantite ===
                                (float)
                                $existingArticle->quantite;
                        }

                        if ($sameQuantity) {

                            $matchedArticle =
                                $existingArticle;

                            break;
                        }
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | 2. FALLBACK : désignation seulement
                    |--------------------------------------------------------------------------
                    */

                    if (!$matchedArticle) {

                        foreach (
                            $existingArticles
                            as $existingArticle
                        ) {

                            if (
                                in_array(
                                    $existingArticle->id,
                                    $usedArticleIds,
                                    true
                                )
                            ) {
                                continue;
                            }

                            $existingDesignation =
                                mb_strtolower(
                                    preg_replace(
                                        '/\s+/u',
                                        ' ',
                                        trim(
                                            (string)
                                            $existingArticle->designation
                                        )
                                    ),
                                    'UTF-8'
                                );

                            if (
                                $existingDesignation ===
                                $normalizedDesignation
                            ) {

                                $matchedArticle =
                                    $existingArticle;

                                break;
                            }
                        }
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | ARTICLE EXISTANT
                    |--------------------------------------------------------------------------
                    */

                    if ($matchedArticle) {

                        $articleId =
                            $matchedArticle->id;

                        $usedArticleIds[] =
                            $articleId;

                    } else {

                        /*
                        |--------------------------------------------------------------------------
                        | ARTICLE ABSENT :
                        | on le crée pour la répartition.
                        |--------------------------------------------------------------------------
                        */

                        $article =
                            Article::create(
                                [
                                    'consultation_id' =>
                                        $consultationModel->id,

                                    'designation' =>
                                        $designation,

                                    'description' =>
                                        $description !== null
                                            ? trim(
                                                (string)
                                                $description
                                            )
                                            : null,

                                    'quantite' =>
                                        $quantite ?? 0,

                                    'prix_unitaire' =>
                                        null,

                                    'prix_total' =>
                                        null,
                                ]
                            );

                        $articleId =
                            $article->id;

                        /*
                        | Ajouter immédiatement
                        | le nouvel article à la collection.
                        */

                        $existingArticles->push(
                            $article
                        );

                        $usedArticleIds[] =
                            $articleId;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | MAPPING ARTICLE
                    |--------------------------------------------------------------------------
                    */

                    $articleIds[
                        $articleIndex
                    ] =
                        $articleId;

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | CAS CONSULTATION
                |--------------------------------------------------------------------------
                */

                $prixUnitaire =
                    $articleData['prix_unitaire']
                    ?? $articleData['prix_unitaire_ht']
                    ?? null;

                $prixTotal =
                    $articleData['prix_total']
                    ?? $articleData['prix_total_ht']
                    ?? null;

                $article =
                    Article::create(
                        [
                            'consultation_id' =>
                                $consultationModel->id,

                            'designation' =>
                                $designation,

                            'description' =>
                                $description !== null
                                    ? trim(
                                        (string)
                                        $description
                                    )
                                    : null,

                            'quantite' =>
                                $quantite ?? 0,

                            'prix_unitaire' =>
                                $prixUnitaire,

                            'prix_total' =>
                                $prixTotal,
                        ]
                    );

                $articleIds[
                    $articleIndex
                ] =
                    $article->id;
            }

            /*
            |--------------------------------------------------------------------------
            | FOURNISSEURS
            |--------------------------------------------------------------------------
            */

            $supplierIds = [];
            $supplierMap = [];

            foreach (
                ($summary['fournisseurs'] ?? [])
                as $supplierData
            ) {

                $name = trim(
                    (string) (
                        $supplierData['name']
                        ?? $supplierData['nom']
                        ?? $supplierData['fournisseur']
                        ?? ''
                    )
                );

                if ($name === '') {
                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | FOURNISSEUR AUTOMATIQUE
                |--------------------------------------------------------------------------
                */

                $isAutomaticSupplier =
                    preg_match(
                        '/^Fournisseur\s+[A-Z]+$/iu',
                        $name
                    );

                /*
                |--------------------------------------------------------------------------
                | VALIDATION
                |--------------------------------------------------------------------------
                */

                if (
                    !$isAutomaticSupplier &&
                    !$this->isValidSupplierName(
                        $name
                    )
                ) {
                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | INFORMATIONS FOURNISSEUR
                |--------------------------------------------------------------------------
                */

                $matricule =
                    trim(
                        (string) (
                            $supplierData[
                                'matricule_fiscal'
                            ]
                            ??
                            $supplierData[
                                'matricule'
                            ]
                            ??
                            ''
                        )
                    );

                $email =
                    trim(
                        (string) (
                            $supplierData['email']
                            ?? ''
                        )
                    );

                $telephone =
                    trim(
                        (string) (
                            $supplierData['telephone']
                            ??
                            $supplierData['tel']
                            ??
                            ''
                        )
                    );

                /*
                |--------------------------------------------------------------------------
                | CRÉER / RÉCUPÉRER LE FOURNISSEUR
                |--------------------------------------------------------------------------
                */

                if ($matricule !== '') {

                    $supplier =
                        fournisseurs::firstOrCreate(
                            [
                                'matricule_fiscal' =>
                                    $matricule,
                            ],
                            [
                                'name' =>
                                    $name,

                                'email' =>
                                    $email !== ''
                                        ? $email
                                        : null,

                                'telephone' =>
                                    $telephone !== ''
                                        ? $telephone
                                        : null,
                            ]
                        );

                    if (
                        trim(
                            (string)
                            $supplier->name
                        ) === '' &&
                        $name !== ''
                    ) {

                        $supplier->name =
                            $name;

                        $supplier->save();
                    }

                } else {

                    $supplier =
                        fournisseurs::firstOrCreate(
                            [
                                'name' =>
                                    $name,
                            ],
                            [
                                'matricule_fiscal' =>
                                    null,

                                'email' =>
                                    $email !== ''
                                        ? $email
                                        : null,

                                'telephone' =>
                                    $telephone !== ''
                                        ? $telephone
                                        : null,
                            ]
                        );
                }

                $supplierIds[] =
                    $supplier->id;

                /*
                |--------------------------------------------------------------------------
                | MAPPING FOURNISSEUR → ID
                |--------------------------------------------------------------------------
                */

                $supplierKey =
                    mb_strtolower(
                        preg_replace(
                            '/\s+/u',
                            ' ',
                            trim($name)
                        ),
                        'UTF-8'
                    );

                $supplierMap[
                    $supplierKey
                ] =
                    $supplier->id;
            }

            /*
            |--------------------------------------------------------------------------
            | ATTACHER LES FOURNISSEURS
            |--------------------------------------------------------------------------
            */

            if (!empty($supplierIds)) {

                $consultationModel
                    ->fournisseurs()
                    ->syncWithoutDetaching(
                        $supplierIds
                    );
            }

            /*
            |--------------------------------------------------------------------------
            | OFFRES DU PV / RÉPARTITION
            |--------------------------------------------------------------------------
            */

            if (
                $type === 'pv' ||
                $type === 'repartition'
            ) {

                foreach (
                    ($summary['offers'] ?? [])
                    as $offerData
                ) {

                    /*
                    |--------------------------------------------------------------------------
                    | FOURNISSEUR
                    |--------------------------------------------------------------------------
                    */

                    $supplierId =
                        $offerData[
                            'fournisseur_id'
                        ] ?? null;

                    $supplierName =
                        trim(
                            (string) (
                                $offerData[
                                    'fournisseur'
                                ]
                                ??
                                $offerData[
                                    'supplier'
                                ]
                                ??
                                $offerData[
                                    'nom_fournisseur'
                                ]
                                ??
                                ''
                            )
                        );

                    /*
                    |--------------------------------------------------------------------------
                    | RETROUVER L'ID DU FOURNISSEUR
                    |--------------------------------------------------------------------------
                    */

                    if (
                        !$supplierId &&
                        $supplierName !== ''
                    ) {

                        $supplierKey =
                            mb_strtolower(
                                preg_replace(
                                    '/\s+/u',
                                    ' ',
                                    trim(
                                        $supplierName
                                    )
                                ),
                                'UTF-8'
                            );

                        if (
                            isset(
                                $supplierMap[
                                    $supplierKey
                                ]
                            )
                        ) {

                            $supplierId =
                                $supplierMap[
                                    $supplierKey
                                ];
                        }
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | CHERCHER DANS LES FOURNISSEURS
                    | DE LA CONSULTATION
                    |--------------------------------------------------------------------------
                    */

                    if (
                        !$supplierId &&
                        $supplierName !== ''
                    ) {

                        $supplier = null;

                        foreach (
                            $consultationModel
                                ->fournisseurs()
                                ->get()
                            as $existingSupplier
                        ) {

                            $existingName =
                                trim(
                                    $this->text(
                                        $existingSupplier->name
                                    )
                                );

                            $normalizedExistingName =
                                mb_strtolower(
                                    preg_replace(
                                        '/\s+/u',
                                        ' ',
                                        $existingName
                                    ),
                                    'UTF-8'
                                );

                            $normalizedSupplierName =
                                mb_strtolower(
                                    preg_replace(
                                        '/\s+/u',
                                        ' ',
                                        $supplierName
                                    ),
                                    'UTF-8'
                                );

                            if (
                                $normalizedExistingName ===
                                $normalizedSupplierName
                            ) {

                                $supplier =
                                    $existingSupplier;

                                break;
                            }
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | CHERCHER GLOBALEMENT
                        |--------------------------------------------------------------------------
                        */

                        if (!$supplier) {

                            foreach (
                                fournisseurs::
                                whereNotNull('name')
                                ->get()
                                as $existingSupplier
                            ) {

                                $existingName =
                                    trim(
                                        $this->text(
                                            $existingSupplier->name
                                        )
                                    );

                                $normalizedExistingName =
                                    mb_strtolower(
                                        preg_replace(
                                            '/\s+/u',
                                            ' ',
                                            $existingName
                                        ),
                                        'UTF-8'
                                    );

                                $normalizedSupplierName =
                                    mb_strtolower(
                                        preg_replace(
                                            '/\s+/u',
                                            ' ',
                                            $supplierName
                                        ),
                                        'UTF-8'
                                    );

                                if (
                                    $normalizedExistingName ===
                                    $normalizedSupplierName
                                ) {

                                    $supplier =
                                        $existingSupplier;

                                    break;
                                }
                            }
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | CRÉER LE FOURNISSEUR S'IL N'EXISTE PAS
                        |--------------------------------------------------------------------------
                        */

                        if (!$supplier) {

                            $isAutomaticSupplier =
                                preg_match(
                                    '/^Fournisseur\s+[A-Z]+$/iu',
                                    $supplierName
                                );

                            if (
                                !$isAutomaticSupplier &&
                                !$this->isValidSupplierName(
                                    $supplierName
                                )
                            ) {
                                continue;
                            }

                            $supplier =
                                fournisseurs::firstOrCreate(
                                    [
                                        'name' =>
                                            $supplierName,
                                    ],
                                    [
                                        'matricule_fiscal' =>
                                            null,

                                        'email' =>
                                            null,

                                        'telephone' =>
                                            null,
                                    ]
                                );
                        }

                        $supplierId =
                            $supplier->id;

                        /*
                        |--------------------------------------------------------------------------
                        | ATTACHER LE FOURNISSEUR
                        |--------------------------------------------------------------------------
                        */

                        $consultationModel
                            ->fournisseurs()
                            ->syncWithoutDetaching(
                                [
                                    $supplierId,
                                ]
                            );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | PAS DE FOURNISSEUR
                    |--------------------------------------------------------------------------
                    */

                    if (!$supplierId) {
                        continue;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | ARTICLE DE L'OFFRE
                    |--------------------------------------------------------------------------
                    */

                    $articleIndex =
                        $offerData[
                            'article_index'
                        ] ?? null;

                    $articleId = null;

                    /*
                    |--------------------------------------------------------------------------
                    | 1. CHERCHER PAR article_index
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $articleIndex !== null &&
                        isset(
                            $articleIds[
                                $articleIndex
                            ]
                        )
                    ) {

                        $articleId =
                            $articleIds[
                                $articleIndex
                            ];
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | 2. FALLBACK : CHERCHER PAR DÉSIGNATION
                    |--------------------------------------------------------------------------
                    */

                    if (!$articleId) {

                        $offerDesignation =
                            trim(
                                (string) (
                                    $offerData[
                                        'article'
                                    ]
                                    ??
                                    $offerData[
                                        'designation'
                                    ]
                                    ??
                                    ''
                                )
                            );

                        if (
                            $offerDesignation !== ''
                        ) {

                            $normalizedOfferDesignation =
                                mb_strtolower(
                                    preg_replace(
                                        '/\s+/u',
                                        ' ',
                                        trim(
                                            $offerDesignation
                                        )
                                    ),
                                    'UTF-8'
                                );

                            foreach (
                                $existingArticles
                                as $existingArticle
                            ) {

                                $existingDesignation =
                                    mb_strtolower(
                                        preg_replace(
                                            '/\s+/u',
                                            ' ',
                                            trim(
                                                (string)
                                                $existingArticle
                                                    ->designation
                                            )
                                        ),
                                        'UTF-8'
                                    );

                                if (
                                    $existingDesignation ===
                                    $normalizedOfferDesignation
                                ) {

                                    $articleId =
                                        $existingArticle->id;

                                    break;
                                }
                            }
                        }
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | ARTICLE INTROUVABLE
                    |--------------------------------------------------------------------------
                    */

                    if (!$articleId) {

                        throw new \RuntimeException(
                            'Article introuvable pour l’offre '
                            . 'du fournisseur « '
                            . (
                                $supplierName
                                ?: 'inconnu'
                            )
                            . ' ». Désignation : « '
                            . (
                                $offerData[
                                    'article'
                                ]
                                ??
                                $offerData[
                                    'designation'
                                ]
                                ??
                                'vide'
                            )
                            . ' »'
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | PRIX HT UNIQUEMENT
                    |--------------------------------------------------------------------------
                    */

                    $rawPrice =
                        $offerData[
                            'prix_unitaire_ht'
                        ]
                        ??
                        $offerData[
                            'prix'
                        ]
                        ??
                        null;

                    $price =
                        $this->price(
                            $rawPrice
                        );

                    if ($price === null) {
                        continue;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | ENREGISTRER L'OFFRE
                    |--------------------------------------------------------------------------
                    */

                    offre::updateOrCreate(
                        [
                            'consultation_id' =>
                                $consultationModel->id,

                            'article_id' =>
                                $articleId,

                            'fournisseur_id' =>
                                $supplierId,
                        ],
                        [
                            'prix_unitaire_ht' =>
                                $price,
                        ]
                    );
                }
            }

            /*
            |--------------------------------------------------------------------------
            | FIN
            |--------------------------------------------------------------------------
            */

            return 1;
        }
    );
}




  

    /*
    |--------------------------------------------------------------------------
    | PARSER PRINCIPAL
    |--------------------------------------------------------------------------
    */
    private function parse(string $path, ?string $sheetName = null, ?string $type = null): array
{
    if (!file_exists($path)) {
        throw new \RuntimeException("Le fichier Excel est introuvable : {$path}");
    }

    $spreadsheet = IOFactory::load($path);

    $sheet = $sheetName !== null
        ? $spreadsheet->getSheetByName($sheetName)
        : $spreadsheet->getActiveSheet();

    if (!$sheet) {
        throw new \RuntimeException(
            "La feuille Excel demandée est introuvable : {$sheetName}"
        );
    }

    $rows = $sheet->toArray(null, true, true, true);

    $reference = $this->referenceFromExcelRows($rows);
    $objet = $this->objetFromExcelRows($rows);
    $dates = $this->datesFromExcelRows($rows);

    $table = $this->extractTable($rows, $type);

    $fournisseurs = [];
    $offers = [];

     if ($type === 'pv') {

        /*
         * 1. Détection des fournisseurs
         */
        $fournisseurs = $this->extractSuppliers($rows, $table);

        /*
         * 2. Détection des offres
         */
        $offers = $this->extractOffers(
            $rows,
            $table['articles'] ?? [],
            $fournisseurs,
            $type
        );

        /*
         * 3. Si extractSuppliers() n'a pas trouvé les fournisseurs,
         *    on les récupère directement depuis les offres.
         */
        $fournisseursUniques = [];

        foreach ($offers as $offer) {

            $nomFournisseur = trim((string) (
                $offer['fournisseur']
                ?? $offer['supplier']
                ?? $offer['nom_fournisseur']
                ?? ''
            ));

            if ($nomFournisseur === '') {
                continue;
            }

            $cle = mb_strtolower(
                preg_replace('/\s+/u', ' ', $nomFournisseur),
                'UTF-8'
            );

            if (!isset($fournisseursUniques[$cle])) {

                $fournisseursUniques[$cle] = [
                    'name' => $nomFournisseur,
                    'nom' => $nomFournisseur,
                    'fournisseur' => $nomFournisseur,
                ];
            }
        }

        if (!empty($fournisseursUniques)) {
            $fournisseurs = array_values($fournisseursUniques);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | RÉPARTITION
    |--------------------------------------------------------------------------
    |
    | Une répartition concerne UN SEUL fournisseur.
    | Le nom du fournisseur se trouve dans une ligne contenant "المزود".
    |
    */
    if ($type === 'repartition') {

        $fournisseurs =
            $this->extractRepartitionSupplier(
                $rows
            );

        $offers =
            $this->extractRepartitionOffers(
                $rows,
                $table['articles'] ?? [],
                $fournisseurs
            );
    }

    

    return [
        'reference' => $reference,
        'objet' => $objet,
        'date_lancement' => $dates['date_lancement'] ?? null,
        'date_limite' => $dates['date_limite'] ?? null,

        'articles' => $table['articles'] ?? [],

        // IMPORTANT
        'fournisseurs' => $fournisseurs,

        // IMPORTANT
        'offers' => $offers,

        'errors' => $table['errors'] ?? [],
        'warnings' => $table['warnings'] ?? [],
    ];
}

    /*
    |--------------------------------------------------------------------------
    | RÉFÉRENCE
    |--------------------------------------------------------------------------
    */
    private function referenceFromExcelRows(array $rows): ?string
    {
        foreach ($rows as $row) {
            foreach ($row as $value) {
                $text = $this->text($value);

                if ($text === '') {
                    continue;
                }

                $normalized = $this->normalizeHeader($text);

                $patterns = [
                    '/استشارة\s+عدد\s*[:：]?\s*(.+)$/iu',
                    '/استشاره\s+عدد\s*[:：]?\s*(.+)$/iu',
                    '/consultation\s*(?:n|no|n°|numero|numéro)?\s*[:：]?\s*(.+)$/iu',
                    '/référence\s*[:：]?\s*(.+)$/iu',
                    '/reference\s*[:：]?\s*(.+)$/iu',
                ];

                foreach ($patterns as $pattern) {
                    if (preg_match($pattern, $text, $matches)) {
                        $reference = $this->normalizeReference(
                            trim($matches[1])
                        );

                        if ($reference !== '') {
                            return $reference;
                        }
                    }
                }

                /*
                 * Cas fréquent : le libellé et la référence sont
                 * dans deux cellules différentes.
                 */
                if (
                    str_contains($normalized, 'استشارة عدد') ||
                    str_contains($normalized, 'استشاره عدد') ||
                    str_contains($normalized, 'consultation')
                ) {
                    continue;
                }
            }
        }

        /*
         * Deuxième passage : chercher une référence de forme
         * 17/2026, 03/2026, etc. dans les cellules.
         */
        foreach ($rows as $row) {
            foreach ($row as $value) {
                $text = $this->text($value);

                if (
                    preg_match(
                        '/(?<!\d)(\d{1,4}\s*\/\s*\d{4})(?!\d)/u',
                        $text,
                        $matches
                    )
                ) {
                    return $this->normalizeReference(
                        $matches[1]
                    );
                }
            }
        }

        return null;
    }

    private function normalizeReference(?string $reference): string
    {
        $reference = $this->text($reference);

        if ($reference === '') {
            return '';
        }

        $reference = $this->normalizeArabicDigits($reference);
        $reference = trim($reference);

        /*
         * Si la cellule contient du texte autour de la référence,
         * récupérer uniquement le numéro de consultation.
         */
        if (
            preg_match(
                '/(?<!\d)(\d{1,4})\s*\/\s*(\d{4})(?!\d)/u',
                $reference,
                $matches
            )
        ) {
            return $matches[1] . '/' . $matches[2];
        }

        return trim($reference);
    }

    /*
    |--------------------------------------------------------------------------
    | OBJET
    |--------------------------------------------------------------------------
    */
    private function objetFromExcelRows(array $rows): ?string
    {
        foreach ($rows as $row) {
            foreach ($row as $value) {
                $text = $this->text($value);

                if ($text === '') {
                    continue;
                }

                if (
                    preg_match(
                        '/^(?:الموضوع|موضوع|objet)\s*[:：]\s*(.+)$/iu',
                        $text,
                        $matches
                    )
                ) {
                    return trim($matches[1]);
                }
            }
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | DATES
    |--------------------------------------------------------------------------
    */
    private function datesFromExcelRows(array $rows): array
    {
        $result = [
            'date_lancement' => null,
            'date_limite' => null,
        ];

        foreach ($rows as $row) {
            foreach ($row as $value) {
                $text = $this->text($value);

                if ($text === '') {
                    continue;
                }

                $normalized = $this->normalizeHeader($text);

                $date = $this->extractDateValue($value);

                if ($date === null) {
                    if (
                        preg_match(
                            '/(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})/u',
                            $text,
                            $matches
                        )
                    ) {
                        $date = $this->normalizeDateString(
                            $matches[1]
                        );
                    }
                }

                if ($date === null) {
                    continue;
                }

                if (
                    $result['date_lancement'] === null &&
                    (
                        str_contains($normalized, 'date lancement') ||
                        str_contains($normalized, 'date de lancement') ||
                        str_contains($normalized, 'تاريخ الانطلاق') ||
                        str_contains($normalized, 'تاريخ بداية')
                    )
                ) {
                    $result['date_lancement'] = $date;
                    continue;
                }

                if (
                    $result['date_limite'] === null &&
                    (
                        str_contains($normalized, 'date limite') ||
                        str_contains($normalized, 'dernier delai') ||
                        str_contains($normalized, 'dernier délai') ||
                        str_contains($normalized, 'آخر أجل') ||
                        str_contains($normalized, 'اخر اجل') ||
                        str_contains($normalized, 'تاريخ الغلق') ||
                        str_contains($normalized, 'تاريخ اخر')
                    )
                ) {
                    $result['date_limite'] = $date;
                }
            }
        }

        return $result;
    }

    private function extractDateValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            if (
                is_numeric($value) &&
                (float) $value > 20000 &&
                (float) $value < 60000
            ) {
                return ExcelDate::excelToDateTimeObject(
                    (float) $value
                )->format('Y-m-d');
            }
        } catch (Throwable) {
            // Continuer avec les formats texte.
        }

        return null;
    }

    private function normalizeDateString(string $value): ?string
    {
        $value = trim(
            $this->normalizeArabicDigits($value)
        );

        $parts = preg_split(
            '/[\/\-]/',
            $value
        );

        if (count($parts) !== 3) {
            return null;
        }

        [$day, $month, $year] = $parts;

        if (strlen($year) === 2) {
            $year = '20' . $year;
        }

        if (
            !checkdate(
                (int) $month,
                (int) $day,
                (int) $year
            )
        ) {
            return null;
        }

        return sprintf(
            '%04d-%02d-%02d',
            (int) $year,
            (int) $month,
            (int) $day
        );
    }

    /*
    |--------------------------------------------------------------------------
    | TABLEAU DES ARTICLES
    |--------------------------------------------------------------------------
    */
private function extractTable(
    array $rows,
    ?string $type = null
): array {

    /*
    |--------------------------------------------------------------------------
    | IMPORTANT
    |--------------------------------------------------------------------------
    | On conserve les vraies clés Excel.
    |
    | Exemple :
    | Excel ligne 8  -> clé 8
    | Excel ligne 9  -> clé 9
    |
    | NE PAS utiliser array_values() ici pour déterminer excel_row.
    |--------------------------------------------------------------------------
    */

    $rowsList = $rows;

    $headerPosition = null;
    $designationColumn = null;
    $quantityColumn = null;

    /*
    |--------------------------------------------------------------------------
    | TROUVER LA LIGNE DES EN-TÊTES
    |--------------------------------------------------------------------------
    */

    foreach ($rowsList as $excelRow => $row) {

        $foundDesignation = null;
        $foundQuantity = null;
        $foundMinimumPrice = null;

        foreach ($row as $column => $value) {

            $header = $this->normalizeHeader($value);

            if ($header === '') {
                continue;
            }

            if (
                $foundDesignation === null &&
                $this->isDesignationHeader($header)
            ) {
                $foundDesignation = $column;
            }

            if (
                $foundQuantity === null &&
                $this->isQuantityHeader($header)
            ) {
                $foundQuantity = $column;
            }

            if (
                $foundMinimumPrice === null &&
                (
                    str_contains(
                        $header,
                        'السعر الفردي الأدنى'
                    ) ||
                    str_contains(
                        $header,
                        'السعر الفردي الادنى'
                    ) ||
                    str_contains(
                        $header,
                        'prix individuel minimum'
                    ) ||
                    str_contains(
                        $header,
                        'prix unitaire minimum'
                    )
                )
            ) {
                $foundMinimumPrice = $column;
            }
        }

        if (
            $foundQuantity !== null &&
            (
                $foundDesignation !== null ||
                $foundMinimumPrice !== null
            )
        ) {

            $headerPosition = $excelRow;

            $quantityColumn = $foundQuantity;
            $designationColumn = $foundDesignation;

            break;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | AUCUN TABLEAU TROUVÉ
    |--------------------------------------------------------------------------
    */

    if (
        $headerPosition === null ||
        $quantityColumn === null
    ) {

        return [
            'articles' => [],
            'errors' => [],
            'warnings' => [
                'Aucun tableau d’articles détecté dans le fichier Excel.'
            ],
        ];
    }

    $headerColumns = array_keys(
        $rowsList[$headerPosition]
    );

    /*
    |--------------------------------------------------------------------------
    | SI LA DÉSIGNATION N'A PAS ÉTÉ TROUVÉE
    |--------------------------------------------------------------------------
    */

    if ($designationColumn === null) {

        $quantityIndex = array_search(
            $quantityColumn,
            $headerColumns,
            true
        );

        if (
            $quantityIndex !== false &&
            $quantityIndex > 0
        ) {

            $designationColumn =
                $headerColumns[$quantityIndex - 1];
        }
    }

    $articles = [];
    $warnings = [];

    /*
    |--------------------------------------------------------------------------
    | PARCOURIR LES VRAIES LIGNES EXCEL
    |--------------------------------------------------------------------------
    */

    $headerFound = false;

    foreach ($rowsList as $excelRow => $row) {

        /*
        |----------------------------------------------------------------------
        | Commencer après l'en-tête
        |----------------------------------------------------------------------
        */

        if (!$headerFound) {

            if ((string) $excelRow === (string) $headerPosition) {
                $headerFound = true;
            }

            continue;
        }

        /*
        |----------------------------------------------------------------------
        | VALEURS DE LA LIGNE
        |----------------------------------------------------------------------
        */

        $allValues = array_map(
            fn ($value) => $this->text($value),
            $row
        );

        /*
        |----------------------------------------------------------------------
        | IGNORER LA LIGNE DES FOURNISSEURS
        |----------------------------------------------------------------------
        |
        | Exemple :
        |
        | Fournisseur A
        | Fournisseur B
        | Fournisseur C
        | Fournisseur D
        |
        | Cette ligne ne doit JAMAIS devenir un article.
        |---------------------------------------------------------------------- 
        */

        $supplierLabelCount = 0;

        foreach ($allValues as $value) {

            $normalizedValue =
                $this->normalizeHeader($value);

            if (
                preg_match(
                    '/^fournisseur\s+[a-z]+$/iu',
                    $normalizedValue
                )
            ) {
                $supplierLabelCount++;
            }
        }

        if ($supplierLabelCount >= 2) {
            continue;
        }

        /*
        |----------------------------------------------------------------------
        | DÉSIGNATION
        |----------------------------------------------------------------------
        */

        $designation = '';

        if ($designationColumn !== null) {

            $designation = $this->text(
                $row[$designationColumn] ?? ''
            );
        }

        /*
        |----------------------------------------------------------------------
        | SI DÉSIGNATION VIDE
        | chercher une cellule texte avant la quantité
        |---------------------------------------------------------------------- 
        */

        if ($designation === '') {

    $columns = array_keys($row);

    $quantityIndex = array_search(
        $quantityColumn,
        $columns,
        true
    );

    if ($quantityIndex !== false) {

        for (
            $i = 0;
            $i < $quantityIndex;
            $i++
        ) {

            $candidate =
                $this->text(
                    $row[$columns[$i]] ?? ''
                );

            if ($candidate === '') {
                continue;
            }

            /*
            | Ne pas prendre le numéro d'article.
            */

            if (
                is_numeric(
                    str_replace(
                        ',',
                        '.',
                        $candidate
                    )
                )
            ) {
                continue;
            }

            /*
            | Ne pas prendre une ligne fournisseur.
            */

            if (
                preg_match(
                    '/^fournisseur\s+[a-z]+$/iu',
                    $this->normalizeHeader(
                        $candidate
                    )
                )
            ) {
                continue;
            }

            /*
            | IMPORTANT :
            | on récupère une désignation de secours
            | mais on NE MODIFIE PAS $designationColumn.
            */

            $designation = $candidate;

            break;
        }
    }
}

                /*
        |----------------------------------------------------------------------
        | FIN DU TABLEAU
        |----------------------------------------------------------------------
        |
        | Exemple :
        | "المجموع باعتبار كل الاداء"
        | "المجموع باعتبار كل الأداء"
        |
        | Cette ligne n'est PAS un article.
        | On arrête complètement la lecture du tableau.
        |---------------------------------------------------------------------- 
        */

        $normalizedDesignation = $this->normalizeHeader($designation);

        if (
            str_contains($normalizedDesignation, 'المجموع') ||
            $this->isEndOfArticleTable(
                $designation,
                $allValues
            )
        ) {
            break;
        }

        /*
        |----------------------------------------------------------------------
        | LIGNE NON ARTICLE
        |----------------------------------------------------------------------
        */

        if (
            $designation === '' ||
            $this->isNonArticleLine(
                $designation
            )
        ) {
            continue;
        }

        /*
        |----------------------------------------------------------------------
        | NE PAS PRENDRE UNE AUTRE LIGNE D'EN-TÊTE
        |----------------------------------------------------------------------
        */

        if (
            $this->isDesignationHeader(
                $this->normalizeHeader(
                    $designation
                )
            )
        ) {
            continue;
        }

        /*
        |----------------------------------------------------------------------
        | QUANTITÉ
        |----------------------------------------------------------------------
        */

        $quantityData =
            $this->parseQuantity(
                $row[$quantityColumn] ?? ''
            );

        $quantity =
            $quantityData['quantity'];

        $description =
            $quantityData['description'];

        /*
        |----------------------------------------------------------------------
        | DESCRIPTION ENTRE DÉSIGNATION ET QUANTITÉ
        |----------------------------------------------------------------------
        */

        $columns = array_keys($row);

        $designationIndex =
            array_search(
                $designationColumn,
                $columns,
                true
            );

        $quantityIndex =
            array_search(
                $quantityColumn,
                $columns,
                true
            );

        if (
            $designationIndex !== false &&
            $quantityIndex !== false &&
            $quantityIndex > $designationIndex + 1
        ) {

            $descriptionParts = [];

            for (
                $i = $designationIndex + 1;
                $i < $quantityIndex;
                $i++
            ) {

                $part =
                    $this->text(
                        $row[$columns[$i]] ?? ''
                    );

                if ($part === '') {
                    continue;
                }

                if (
                    $this->isEndOfArticleTable(
                        $part,
                        [$part]
                    )
                ) {
                    continue;
                }

                /*
                | Ne pas mettre une ligne fournisseur
                | dans la description.
                */

                if (
                    preg_match(
                        '/^fournisseur\s+[a-z]+$/iu',
                        $this->normalizeHeader($part)
                    )
                ) {
                    continue;
                }

                $descriptionParts[] = $part;
            }

            if (!empty($descriptionParts)) {

                $description =
                    trim(
                        implode(
                            ' | ',
                            $descriptionParts
                        )
                    );
            }
        }

        /*
        |----------------------------------------------------------------------
        | PRIX CONSULTATION
        |---------------------------------------------------------------------- 
        */

        $prixUnitaire = null;
        $prixTotal = null;

        foreach ($row as $column => $value) {

            $headerValue =
                $this->normalizeHeader(
                    $rowsList[$headerPosition][$column] ?? ''
                );

            if (
                $prixUnitaire === null &&
                $this->isUnitPriceHeader(
                    $headerValue
                )
            ) {

                $prixUnitaire =
                    $this->price($value);
            }

            if (
                $prixTotal === null &&
                $this->isTotalPriceHeader(
                    $headerValue
                )
            ) {

                $prixTotal =
                    $this->price($value);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | AJOUTER L'ARTICLE
        |--------------------------------------------------------------------------
        |
        | TRÈS IMPORTANT :
        |
        | $excelRow est le vrai numéro de ligne Excel.
        |
        | On ne met PAS $position.
        |--------------------------------------------------------------------------
        */

        $articles[] = [

            'designation' =>
                $designation,

            'description' =>
                $description,

            'quantite' =>
                $quantity,

            'prix_unitaire' =>
                $prixUnitaire,

            'prix_total' =>
                $prixTotal,

            /*
            | VRAIE LIGNE EXCEL
            */
            'excel_row' =>
                $excelRow,
        ];
    }

    return [
        'articles' =>
            $articles,

        'errors' =>
            [],

        'warnings' =>
            $warnings,
    ];
}

    /*
    |--------------------------------------------------------------------------
    | RECONNAISSANCE DES EN-TÊTES
    |--------------------------------------------------------------------------
    */
 private function isQuantityHeader(string $header): bool
{
    $header = $this->normalizeHeader($header);

    return
        $header === 'الكمية' ||
        $header === 'كمية' ||
        $header === 'العدد' ||
        $header === 'عدد' ||
        str_contains($header, 'quantite') ||
        str_contains($header, 'quantité') ||
        str_contains($header, 'quantity');
}

    
private function isDesignationHeader(string $header): bool
{
    $header = $this->normalizeHeader($header);

    return
        $header === 'designation' ||
        $header === 'désignation' ||
        $header === 'article' ||
        $header === 'بيان الفصل' ||
        $header === 'بيان' ||
        $header === 'البيان' ||
        $header === 'بيان المطلوب' ||
        $header === 'بيان المشتريات' ||
        $header === 'البيانات';
}


private function isDescriptionHeader(string $header): bool
{
    $header = $this->normalizeHeader($header);

    return
        $header === 'description' ||
        $header === 'descriptif' ||
        $header === 'caracteristique' ||
        $header === 'caracteristiques' ||
        $header === 'caracteristique tech' ||
        $header === 'caracteristiques tech' ||
        $header === 'caractéristique tech' ||
        $header === 'caractéristiques tech' ||
        $header === 'الخاصيات الفنية' ||
        $header === 'المواصفات الفنية' ||
        $header === 'المواصفات' ||
        $header === 'الوصف';
}



   private function isUnitPriceHeader(string $header): bool
{
    $header = $this->normalizeHeader($header);

    return
        str_contains($header, 'prix unitaire ht') ||
        str_contains($header, 'prix ht') ||
        str_contains($header, 'prix unitaire hors taxe') ||
        str_contains($header, 'hors taxe') ||
        str_contains($header, 'السعر الفردي') ||
        str_contains($header, 'السعر الوحدوي');
}

private function isTotalPriceHeader(string $header): bool
{
    $header = $this->normalizeHeader($header);

    return
        str_contains($header, 'prix total') ||
        str_contains($header, 'total prix') ||
        str_contains($header, 'السعر الجملي') ||
        str_contains($header, 'المبلغ الجملي') ||
        str_contains($header, 'المجموع');
}
    /*
    |--------------------------------------------------------------------------
    | CHIFFRES ARABES
    |--------------------------------------------------------------------------
    */
    private function normalizeArabicDigits(string $value): string
    {
        return strtr(
            $value,
            [
                '٠' => '0',
                '١' => '1',
                '٢' => '2',
                '٣' => '3',
                '٤' => '4',
                '٥' => '5',
                '٦' => '6',
                '٧' => '7',
                '٨' => '8',
                '٩' => '9',
                '۰' => '0',
                '۱' => '1',
                '۲' => '2',
                '۳' => '3',
                '۴' => '4',
                '۵' => '5',
                '۶' => '6',
                '۷' => '7',
                '۸' => '8',
                '۹' => '9',
            ]
        );
    }

    /*  
    |--------------------------------------------------------------------------  
    | NORMALISER HEADER  
    |--------------------------------------------------------------------------  
    */  
  
    private function normalizeHeader(  
        mixed $value  
    ): string {  
        $text = $this->text($value);  
  
        if ($text === '') {  
            return '';  
        }  
  
        $text =  
            $this->normalizeArabicDigits(  
                $text  
            );  
  
        $text =  
            mb_strtolower(  
                $text,  
                'UTF-8'  
            );  
  
        $text =  
            str_replace(  
                [  
                    'أ',  
                    'إ',  
                    'آ',  
                    'ٱ',  
                ],  
                'ا',  
                $text  
            );  
  
        $text =  
            str_replace(  
                'ـ',  
                '',  
                $text  
            );  
  
        $text =  
            str_replace(  
                [  
                    "\r",  
                    "\n",  
                    "\t",  
                    '：',  
                    ':',  
                    '؛',  
                    '،',  
                ],  
                ' ',  
                $text  
            );  
  
        $text =  
            preg_replace(  
                '/\s+/u',  
                ' ',  
                $text  
            );  
  
        return trim($text);  
    }  
  
    /*  
    |--------------------------------------------------------------------------  
    | PARSER QUANTITÉ  
    |--------------------------------------------------------------------------  
    */  
  
    private function parseQuantity(  
        mixed $value  
    ): array {  
        $text =  
            $this->normalizeArabicDigits(  
                $this->text($value)  
            );  
  
        $text = trim($text);  
  
        if ($text === '') {  
            return [  
                'quantity' => 0,  
                'description' => null,  
            ];  
        }  
  
        if (  
            preg_match(  
                '/^(NC|ND)$/iu',  
                $text  
            )  
        ) {  
            return [  
                'quantity' =>  
                    strtoupper($text),  
  
                'description' =>  
                    null,  
            ];  
        }  
  
        if (  
            preg_match(  
                '/^\s*(\d+(?:[.,]\d+)?)\s+(.+)$/u',  
                $text,  
                $matches  
            )  
        ) {  
            $number =  
                str_replace(  
                    ',',  
                    '.',  
                    $matches[1]  
                );  
  
            $description =  
                trim(  
                    $matches[2]  
                );  
  
            $quantity =  
                (float) $number;  
  
            if (  
                floor($quantity) ==  
                $quantity  
            ) {  
                $quantity =  
                    (int) $quantity;  
            }  
  
            return [  
                'quantity' =>  
                    $quantity,  
  
                'description' =>  
                    $description !== ''  
                        ? $description  
                        : null,  
            ];  
        }  
  
        if (  
            is_numeric(  
                str_replace(  
                    ',',  
                    '.',  
                    $text  
                )  
            )  
        ) {  
            $quantity =  
                (float) str_replace(  
                    ',',  
                    '.',  
                    $text  
                );  
  
            if (  
                floor($quantity) ==  
                $quantity  
            ) {  
                $quantity =  
                    (int) $quantity;  
            }  
  
            return [  
                'quantity' =>  
                    $quantity,  
  
                'description' =>  
                    null,  
            ];  
        }  
  
        return [  
            'quantity' =>  
                0,  
  
            'description' =>  
                $text,  
        ];  
    }  
  
    /*  
    |--------------------------------------------------------------------------  
    | FIN DU TABLEAU DES ARTICLES  
    |--------------------------------------------------------------------------  
    |  
    | IMPORTANT :  
    | Dès que la zone des signatures commence,  
    | on arrête complètement l'extraction.  
    |  
    */  
  
    private function isEndOfArticleTable(  
        string $designation,  
        array $allValues = []  
    ): bool {  
        $text =  
            $this->normalizeHeader(  
                $designation  
            );  
  
        $fullText =  
            $this->normalizeHeader(  
                implode(  
                    ' ',  
                    $allValues  
                )  
            );  
  
        if (  
            $text === '' &&  
            $fullText === ''  
        ) {  
            return false;  
        }  
  
        /*  
        |--------------------------------------------------------------------------  
        | MOTS-CLÉS DE LA ZONE DES SIGNATURES  
        |--------------------------------------------------------------------------  
        */  
  
        $signatureKeywords = [  
            'الإمضاء',  
            'الامضاء',  
            'إمضاء',  
            'امضاء',  
  
            'الإمضاءات',  
            'الامضاءات',  
            'إمضاءات',  
            'امضاءات',  
  
            'التوقيع',  
            'التوقيعات',  
            'توقيع',  
            'توقيعات',  
  
            'signature',  
            'signatures',  
            'cachet',  
            'cachet et signature',  
            'signature et cachet',  
        ];  
  
        foreach (  
            $signatureKeywords as $keyword  
        ) {  
            $normalizedKeyword =  
                $this->normalizeHeader(  
                    $keyword  
                );  
  
            if (  
                str_contains(  
                    $text,  
                    $normalizedKeyword  
                ) ||  
                str_contains(  
                    $fullText,  
                    $normalizedKeyword  
                )  
            ) {  
                return true;  
            }  
        }  
  
        /*  
        |--------------------------------------------------------------------------  
        | LIGNES DE SIGNATURE  
        |--------------------------------------------------------------------------  
        |  
        | Exemple :  
        |  
        | د: ...........................................  
        | محمد غالي: ..................................  
        |  
        */  
  
        if (  
            preg_match(  
                '/(?:\.{5,}|…{3,})/u',  
                $fullText  
            )  
        ) {  
            return true;  
        }  
  
        return false;  
    }  
  
    /*  
    |--------------------------------------------------------------------------  
    | LIGNE NON ARTICLE  
    |--------------------------------------------------------------------------  
    */  
  
    private function isNonArticleLine(  
        string $designation  
    ): bool {  
        $text =  
            $this->normalizeHeader(  
                $designation  
            );  
  
        if ($text === '') {  
            return true;  
        }  
  
        $ignored = [  
            'إمضاء وختم المزود',  
            'إمضاء و ختم المزود',  
  
            'امضاء وختم المزود',  
            'امضاء و ختم المزود',  
  
            'إمضاء المزود',  
            'امضاء المزود',  
  
            'ختم المزود',  
  
            'ختم و إمضاء المزود',  
            'ختم وامضاء المزود',  
  
            'signature et cachet du fournisseur',  
            'signature et cachet fournisseur',  
  
            'signature fournisseur',  
  
            'cachet fournisseur',  
            'cachet du fournisseur',  
  
            'total',  
            'totale',  
  
            'total général',  
            'total general',  
  
            'المجموع',  
            'مجموع',  
            'المجموع العام',  
  
            'observation',  
            'observations',  
  
            'remarque',  
            'remarques',  
  
            'ملاحظة',  
            'ملاحظات',  
        ];  
  
        foreach ($ignored as $item) {  
            if (  
                $text ===  
                $this->normalizeHeader(  
                    $item  
                )  
            ) {  
                return true;  
            }  
        }  
  
        if (  
            (  
                str_contains(  
                    $text,  
                    'إمضاء'  
                ) ||  
                str_contains(  
                    $text,  
                    'امضاء'  
                )  
            ) &&  
            str_contains(  
                $text,  
                'مزود'  
            )  
        ) {  
            return true;  
        }  
  
        if (  
            str_contains(  
                $text,  
                'signature'  
            ) &&  
            str_contains(  
                $text,  
                'fournisseur'  
            )  
        ) {  
            return true;  
        }  
  
        if (  
            str_contains(  
                $text,  
                'ختم'  
            ) &&  
            str_contains(  
                $text,  
                'مزود'  
            )  
        ) {  
            return true;  
        }  
  
        if (  
            str_contains(  
                $text,  
                'cachet'  
            ) &&  
            str_contains(  
                $text,  
                'fournisseur'  
            )  
        ) {  
            return true;  
        }  
  
        return false;  
    }  
  
    /*  
    |--------------------------------------------------------------------------  
    | PARSER PRIX  
    |--------------------------------------------------------------------------  
    */  
  
    private function price(  
        mixed $value  
    ): ?float {  
        if (  
            $value === null ||  
            trim((string) $value) === ''  
        ) {  
            return null;  
        }  
  
        $text =  
            $this->normalizeArabicDigits(  
                $this->text($value)  
            );  
  
        $text = trim($text);  
  
        if (  
            preg_match(  
                '/^(NC|ND)$/iu',  
                $text  
            )  
        ) {  
            return null;  
        }  
  
        $text =  
            str_replace(  
                [  
                    ' ',  
                    "\u{00A0}",  
                    'DT',  
                    'dt',  
                    'TND',  
                    'tnd',  
                ],  
                '',  
                $text  
            );  
  
        if (  
            str_contains(  
                $text,  
                ','  
            ) &&  
            str_contains(  
                $text,  
                '.'  
            )  
        ) {  
            if (  
                strrpos(  
                    $text,  
                    ','  
                ) >  
                strrpos(  
                    $text,  
                    '.'  
                )  
            ) {  
                $text =  
                    str_replace(  
                        '.',  
                        '',  
                        $text  
                    );  
  
                $text =  
                    str_replace(  
                        ',',  
                        '.',  
                        $text  
                    );  
            } else {  
                $text =  
                    str_replace(  
                        ',',  
                        '',  
                        $text  
                    );  
            }  
        } elseif (  
            str_contains(  
                $text,  
                ','  
            )  
        ) {  
            $text =  
                str_replace(  
                    ',',  
                    '.',  
                    $text  
                );  
        }  
  
        if (  
            preg_match(  
                '/-?\d+(?:\.\d+)?/',  
                $text,  
                $matches  
            )  
        ) {  
            return (float) $matches[0];  
        }  
  
        return null;  
    }  
  
    /*  
    |--------------------------------------------------------------------------  
    | FOURNISSEURS  
    |--------------------------------------------------------------------------  
    */  
/*
|--------------------------------------------------------------------------
| FOURNISSEUR DE LA RÉPARTITION
|--------------------------------------------------------------------------
|
| La répartition concerne un seul fournisseur.
|
| Exemple :
|
| المزود : Société ABC
|
| ou :
|
| المزود
| Société ABC
|
*/
private function extractRepartitionSupplier(
    array $rows
): array {

    foreach ($rows as $excelRow => $row) {

        foreach ($row as $column => $value) {

            $text = $this->text($value);

            if ($text === '') {
                continue;
            }

            $normalized = $this->normalizeHeader($text);

            /*
            |--------------------------------------------------------------------------
            | Chercher "المزود"
            |--------------------------------------------------------------------------
            */

            if (
                $normalized === 'المزود' ||
                $normalized === 'المزود :' ||
                $normalized === 'المزوّد'
            ) {

                /*
                |--------------------------------------------------------------------------
                | CAS 1 :
                | Le nom est dans la cellule suivante
                |--------------------------------------------------------------------------
                */

                $columns = array_keys($row);

                $currentIndex =
                    array_search(
                        $column,
                        $columns,
                        true
                    );

                if (
                    $currentIndex !== false &&
                    isset($columns[$currentIndex + 1])
                ) {

                    $nextColumn =
                        $columns[$currentIndex + 1];

                    $supplierName =
                        $this->text(
                            $row[$nextColumn] ?? ''
                        );

                    if (
                        $supplierName !== '' &&
                        $this->isValidSupplierName(
                            $supplierName
                        )
                    ) {

                        return [[
                            'name' =>
                                $supplierName,

                            'nom' =>
                                $supplierName,

                            'fournisseur' =>
                                $supplierName,

                            'excel_row' =>
                                $excelRow,

                            'column' =>
                                $nextColumn,
                        ]];
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | CAS 2 :
                | Chercher le nom dans les cellules suivantes
                | de la même ligne.
                |--------------------------------------------------------------------------
                */

                foreach ($row as $nextColumn => $nextValue) {

                    if ($nextColumn === $column) {
                        continue;
                    }

                    $supplierName =
                        $this->text(
                            $nextValue
                        );

                    if ($supplierName === '') {
                        continue;
                    }

                    /*
                    | Ne pas prendre un autre libellé.
                    */

                    if (
                        $this->normalizeHeader(
                            $supplierName
                        ) === 'المزود'
                    ) {
                        continue;
                    }

                    if (
                        $this->isValidSupplierName(
                            $supplierName
                        )
                    ) {

                        return [[
                            'name' =>
                                $supplierName,

                            'nom' =>
                                $supplierName,

                            'fournisseur' =>
                                $supplierName,

                            'excel_row' =>
                                $excelRow,

                            'column' =>
                                $nextColumn,
                        ]];
                    }
                }
            }

            /*
            |--------------------------------------------------------------------------
            | CAS 3 :
            | "المزود : Société ABC" dans une seule cellule
            |--------------------------------------------------------------------------
            */

            if (
                preg_match(
                    '/المزو?د\s*[:：]\s*(.+)$/iu',
                    $text,
                    $matches
                )
            ) {

                $supplierName =
                    trim($matches[1]);

                if (
                    $supplierName !== '' &&
                    $this->isValidSupplierName(
                        $supplierName
                    )
                ) {

                    return [[
                        'name' =>
                            $supplierName,

                        'nom' =>
                            $supplierName,

                        'fournisseur' =>
                            $supplierName,

                        'excel_row' =>
                            $excelRow,
                    ]];
                }
            }
        }
    }

    return [];
}

/*
|--------------------------------------------------------------------------
| OFFRES DE LA RÉPARTITION
|--------------------------------------------------------------------------
|
| Une répartition possède un seul fournisseur.
|
| Le prix est dans le tableau, sur la même ligne que l'article.
|--------------------------------------------------------------------------
*/
private function extractRepartitionOffers(
    array $rows,
    array $articles,
    array $fournisseurs
): array {

    if (
        empty($articles) ||
        empty($fournisseurs)
    ) {
        return [];
    }

    /*
    |--------------------------------------------------------------------------
    | UN SEUL FOURNISSEUR
    |--------------------------------------------------------------------------
    */

    $supplier =
        $fournisseurs[0];

    $supplierName =
        trim(
            (string) (
                $supplier['name']
                ?? ''
            )
        );

    if ($supplierName === '') {
        return [];
    }

    /*
    |--------------------------------------------------------------------------
    | Trouver la colonne du prix
    |--------------------------------------------------------------------------
    */

    $headerRow = null;
    $priceColumn = null;

    foreach ($rows as $excelRow => $row) {

        $hasDesignation = false;
        $hasQuantity = false;

        foreach ($row as $column => $value) {

            $header =
                $this->normalizeHeader(
                    $value
                );

            if (
                $this->isDesignationHeader(
                    $header
                )
            ) {
                $hasDesignation = true;
            }

            if (
                $this->isQuantityHeader(
                    $header
                )
            ) {
                $hasQuantity = true;
            }

            /*
            |--------------------------------------------------------------------------
            | Prix HT
            |--------------------------------------------------------------------------
            */

            if (
                $priceColumn === null &&
                (
                    str_contains(
                        $header,
                        'prix unitaire ht'
                    ) ||
                    str_contains(
                        $header,
                        'prix ht'
                    ) ||
                    str_contains(
                        $header,
                        'prix unitaire hors taxe'
                    ) ||
                    str_contains(
                        $header,
                        'السعر الفردي'
                    ) ||
                    str_contains(
                        $header,
                        'السعر الوحدوي'
                    )
                )
            ) {
                $priceColumn = $column;
            }
        }

        if (
            $hasDesignation &&
            $hasQuantity
        ) {
            $headerRow = $excelRow;

            /*
            | On a trouvé la ligne d'en-tête.
            */

            break;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Si aucun prix HT n'a été trouvé
    |--------------------------------------------------------------------------
    */

    if (
        $headerRow === null ||
        $priceColumn === null
    ) {
        return [];
    }

    $offers = [];

    /*
    |--------------------------------------------------------------------------
    | Lire le prix de chaque article
    |--------------------------------------------------------------------------
    */

    foreach (
        $articles
        as $articleIndex => $article
    ) {

        $excelRow =
            $article['excel_row']
            ?? null;

        if ($excelRow === null) {
            continue;
        }

        if (
            !array_key_exists(
                $excelRow,
                $rows
            )
        ) {
            continue;
        }

        $row =
            $rows[$excelRow];

        $articleName =
            trim(
                (string) (
                    $article['designation']
                    ?? $article['article']
                    ?? ''
                )
            );

        if ($articleName === '') {
            continue;
        }

        $rawPrice =
            $row[$priceColumn]
            ?? null;

        if (
            $rawPrice === null ||
            trim(
                (string) $rawPrice
            ) === ''
        ) {
            continue;
        }

        $price =
            $this->price(
                $rawPrice
            );

        if ($price === null) {
            continue;
        }

        $offers[] = [

            /*
            | Fournisseur unique
            */
            'fournisseur' =>
                $supplierName,

            'fournisseur_id' =>
                null,

            /*
            | Article correspondant
            */
            'article_index' =>
                $articleIndex,

            'article' =>
                $articleName,

            'designation' =>
                $articleName,

            /*
            | Prix HT uniquement
            */
            'prix_unitaire_ht' =>
                $price,
        ];
    }

    return $offers;
}
private function extractSuppliers(
    array $rows,
    array $table
): array {
    $rowsList = array_values($rows);

    if (empty($rowsList)) {
        return [];
    }

    /*
    |--------------------------------------------------------------------------
    | Trouver la ligne contenant :
    | - Désignation
    | - Quantité
    |--------------------------------------------------------------------------
    */

    $headerPosition = null;
    $designationColumn = null;
    $quantityColumn = null;

    foreach ($rowsList as $position => $row) {

        $foundDesignation = null;
        $foundQuantity = null;

        foreach ($row as $column => $value) {

            $header =
                $this->normalizeHeader($value);

            if ($header === '') {
                continue;
            }

            if (
                $foundDesignation === null &&
                $this->isDesignationHeader($header)
            ) {
                $foundDesignation = $column;
            }

            if (
                $foundQuantity === null &&
                $this->isQuantityHeader($header)
            ) {
                $foundQuantity = $column;
            }
        }

        if (
            $foundDesignation !== null &&
            $foundQuantity !== null
        ) {
            $headerPosition = $position;
            $designationColumn = $foundDesignation;
            $quantityColumn = $foundQuantity;

            break;
        }
    }

    if (
        $headerPosition === null ||
        $quantityColumn === null
    ) {
        return [];
    }

    /*
    |--------------------------------------------------------------------------
    | Trouver la colonne du "prix individuel minimum"
    |--------------------------------------------------------------------------
    */

    $minimumPriceColumn = null;

    foreach (
        $rowsList[$headerPosition]
        as $column => $value
    ) {

        $header =
            $this->normalizeHeader($value);

        if ($header === '') {
            continue;
        }

        if (
            str_contains(
                $header,
                'السعر الفردي الأدنى'
            ) ||
            str_contains(
                $header,
                'السعر الفردي الادنى'
            ) ||
            str_contains(
                $header,
                'prix individuel minimum'
            ) ||
            str_contains(
                $header,
                'prix unitaire minimum'
            )
        ) {
            $minimumPriceColumn = $column;
            break;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Si on ne trouve pas le prix minimum
    |--------------------------------------------------------------------------
    */

    if ($minimumPriceColumn === null) {
        return [];
    }

    /*
    |--------------------------------------------------------------------------
    | IMPORTANT :
    |
    | Dans ton fichier :
    |
    | A = numéro
    | B = désignation
    | C = description
    | D = quantité
    | E = Fournisseur A
    | F = Fournisseur B
    | G = Fournisseur C
    | H = Fournisseur D
    | I = Fournisseur E
    | J = prix minimum
    |
    | Les fournisseurs sont donc ENTRE la colonne quantité
    | et la colonne prix minimum.
    |--------------------------------------------------------------------------
    */

    $columns =
        array_keys(
            $rowsList[$headerPosition]
        );

    $quantityIndex =
        array_search(
            $quantityColumn,
            $columns,
            true
        );

    $minimumPriceIndex =
        array_search(
            $minimumPriceColumn,
            $columns,
            true
        );

    if (
        $quantityIndex === false ||
        $minimumPriceIndex === false
    ) {
        return [];
    }

    /*
    |--------------------------------------------------------------------------
    | Les fournisseurs sont après la quantité
    | et avant le prix minimum.
    |--------------------------------------------------------------------------
    */

    $supplierColumns = [];

    for (
        $i = $quantityIndex + 1;
        $i < $minimumPriceIndex;
        $i++
    ) {

        $column =
            $columns[$i];

        $supplierColumns[] =
            $column;
    }

    /*
    |--------------------------------------------------------------------------
    | Créer les fournisseurs automatiquement
    |--------------------------------------------------------------------------
    */

    $fournisseurs = [];

    foreach (
        $supplierColumns
        as $index => $column
    ) {

        $letter =
            $this->supplierLetter(
                $index
            );

        $fournisseurs[] = [
            'index' =>
                $index,

            'name' =>
                'Fournisseur ' . $letter,

            'nom' =>
                'Fournisseur ' . $letter,

            'fournisseur' =>
                'Fournisseur ' . $letter,

            'column' =>
                $column,
        ];
    }

    return $fournisseurs;
}


/*------------------------------------------------------------------------  
    | VALIDER NOM FOURNISSEUR  
    |--------------------------------------------------------------------------  
    */  
  
    private function isValidSupplierName(  
        string $name  
    ): bool {  
        $name =  
            $this->text($name);  
  
        if ($name === '') {  
            return false;  
        }  
        /*
|--------------------------------------------------------------------------
| VALEURS QUI NE SONT JAMAIS DES FOURNISSEURS
|--------------------------------------------------------------------------
*/

if (
    preg_match(
        '/^(NC|ND)$/iu',
        $name
    )
) {
    return false;
}
  
        if (  
            mb_strlen(  
                $name,  
                'UTF-8'  
            ) > 255  
        ) {  
            return false;  
        }  
  
        $normalized =  
            $this->normalizeHeader(  
                $name  
            );  
  
        $labels = [  
            'fournisseur',  
            'societe',  
            'société',  
  
            'المزود',  
            'المزوّد',  
            'الشركة',  
  
            'المعرف الجبائي',  
            'المعرف الجبائي:',  
  
            'العنوان',  
  
            'الهاتف',  
            'الفاكس',  
  
            'البريد الالكتروني',  
            'البريد الإلكتروني',  
  
            'adresse',  
            'telephone',  
            'téléphone',  
  
            'fax',  
  
            'email',  
            'e-mail',  
  
            'signature',  
            'cachet',  
  
            'إمضاء',  
            'ختم',  
            'الإمضاء',  
            'الإمضاءات',  
            'التوقيع',  
            'التوقيعات',  
            'ممضي',  
  
            'العميد',  
            'العميــد',  
  
            'قابس في',  
        ];  
  
        foreach ($labels as $word) {
            $normalizedWord =
                $this->normalizeHeader(
                    $word
                );

            /*
             * Ne pas rejeter un vrai nom comme :
             * Société Nour
             * Société ABC
             * Fournisseur X
             *
             * On rejette uniquement le libellé lui-même.
             */
            if ($normalized === $normalizedWord) {
                return false;
            }
        }  
  
        if (  
            str_contains(  
                $normalized,  
                'إمضاء'  
            ) ||  
            str_contains(  
                $normalized,  
                'امضاء'  
            ) ||  
            str_contains(  
                $normalized,  
                'الإمضاء'  
            ) ||  
            str_contains(  
                $normalized,  
                'ختم'  
            ) ||  
            str_contains(  
                $normalized,  
                'signature'  
            ) ||  
            str_contains(  
                $normalized,  
                'cachet'  
            )  
        ) {  
            return false;  
        }  
  
        if (  
            str_contains(  
                $normalized,  
                'المعرف الجبائي'  
            ) ||  
            str_contains(  
                $normalized,  
                'العميــد'  
            ) ||  
            str_contains(  
                $normalized,  
                'العميد'  
            ) ||  
            str_contains(  
                $normalized,  
                'قابس في'  
            )  
        ) {  
            return false;  
        }  
  
        return true;  
    }  
  
    /*  
    |--------------------------------------------------------------------------  
    | OFFRES  
    |--------------------------------------------------------------------------  
    */  
private function extractOffers(
    array $rows,
    array $articles,
    array $fournisseurs,
    string $type
): array {
    if ($type !== 'pv') {
        return [];
    }

    if (empty($articles) || empty($fournisseurs)) {
        return [];
    }

    $offers = [];

    foreach ($articles as $articleIndex => $article) {

        /*
        |--------------------------------------------------------------------------
        | VRAIE LIGNE EXCEL
        |--------------------------------------------------------------------------
        */

        $excelRow = $article['excel_row'] ?? null;

        if ($excelRow === null) {
            continue;
        }

        /*
        |--------------------------------------------------------------------------
        | RÉCUPÉRER EXACTEMENT LA LIGNE EXCEL
        |--------------------------------------------------------------------------
        */

        if (!array_key_exists($excelRow, $rows)) {
            continue;
        }

        $row = $rows[$excelRow];

        /*
        |--------------------------------------------------------------------------
        | NOM DE L'ARTICLE
        |--------------------------------------------------------------------------
        */

        $articleName = trim(
            (string) (
                $article['designation']
                ?? $article['article']
                ?? ''
            )
        );

        if ($articleName === '') {
            continue;
        }

        /*
        |--------------------------------------------------------------------------
        | PRIX POUR CHAQUE FOURNISSEUR
        |--------------------------------------------------------------------------
        */

        foreach ($fournisseurs as $supplier) {

            $column = $supplier['column'] ?? null;

            if ($column === null) {
                continue;
            }

            /*
            |--------------------------------------------------------------
            | Lire le prix sur LA MÊME ligne que l'article
            |--------------------------------------------------------------
            */

            $rawPrice = $row[$column] ?? null;

            /*
            | Cellule vide
            */

            if (
                $rawPrice === null ||
                trim(
                    (string) $rawPrice
                ) === ''
            ) {
                continue;
            }

            $value = mb_strtolower(
                trim(
                    $this->text($rawPrice)
                ),
                'UTF-8'
            );

            /*
            |--------------------------------------------------------------------------
            | VALEURS NON CONSIDÉRÉES COMME PRIX
            |--------------------------------------------------------------------------
            */

            if (
                in_array(
                    $value,
                    [
                        'néant',
                        'neant',
                        'néant.',
                        'neant.',
                        'nc',
                        'nd',
                        'n/c',
                        'n/d',
                        '-',
                        '—',
                        '–',
                        'n/a',
                        'na',
                    ],
                    true
                )
            ) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | CONVERSION DU PRIX
            |--------------------------------------------------------------------------
            */

            $price = $this->price($rawPrice);

            if ($price === null) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | OFFRE
            |--------------------------------------------------------------------------
            */

            $offers[] = [
                'fournisseur' =>
                    $supplier['name']
                    ?? (
                        'Fournisseur ' .
                        $this->supplierLetter(
                            $supplier['index'] ?? 0
                        )
                    ),

                'fournisseur_id' =>
                    null,

                /*
                | IMPORTANT :
                | on garde l'index de l'article
                */

                'article_index' =>
                    $articleIndex,

                /*
                | Nom visible dans l'aperçu
                */

                'article' =>
                    $articleName,

                'designation' =>
                    $articleName,

                /*
                | UNIQUEMENT HT
                */

                'prix_unitaire_ht' =>
                    $price,
            ];
        }
    }

    return $offers;
}
  
 private function supplierLetter(int $index): string
{
    $index++;

    $result = '';

    while ($index > 0) {
        $index--;

        $result = chr(65 + ($index % 26)) . $result;

        $index = intdiv($index, 26);
    }

    return $result;
}   /*  
    |--------------------------------------------------------------------------  
    | NETTOYER TEXTE  
    |--------------------------------------------------------------------------  
    */  
  
    private function text(  
        mixed $value  
    ): string {  
        if ($value === null) {  
            return '';  
        }  
  
        if (is_object($value)) {  
            return '';  
        }  
  
        $text =  
            (string) $value;  
  
        /*  
        |--------------------------------------------------------------------------  
        | BOM  
        |--------------------------------------------------------------------------  
        */  
  
        $text =  
            str_replace(  
                "\xEF\xBB\xBF",  
                '',  
                $text  
            );  
  
        /*  
        |--------------------------------------------------------------------------  
        | Espace insécable  
        |--------------------------------------------------------------------------  
        */  
  
        $text =  
            str_replace(  
                [  
                    "\u{00A0}",  
                    "\u{202F}",  
                ],  
                ' ',  
                $text  
            );  
  
        /*  
        |--------------------------------------------------------------------------  
        | Caractères invisibles  
        |--------------------------------------------------------------------------  
        */  
  
        $text =  
            preg_replace(  
                '/[\x{200B}-\x{200D}\x{FEFF}]/u',  
                '',  
                $text  
            );  
  
        /*  
        |--------------------------------------------------------------------------  
        | Espaces multiples  
        |--------------------------------------------------------------------------  
        */  
  
        $text =  
            preg_replace(  
                '/\s+/u',  
                ' ',  
                $text  
            );  
  
        return trim($text);  
    }  
    private function normalizeArabic(?string $text): string
{
    if ($text === null) {
        return '';
    }

    $text = trim($text);

    // Normalisation des espaces
    $text = preg_replace('/\s+/u', ' ', $text);

    // Normalisation des variantes de lettres arabes
    $text = str_replace(
        [
            'أ',
            'إ',
            'آ',
            'ٱ',
            'ى',
            'ي',
            'ة',
            'ؤ',
            'ئ',
        ],
        [
            'ا',
            'ا',
            'ا',
            'ا',
            'ي',
            'ي',
            'ه',
            'و',
            'ي',
        ],
        $text
    );

    // Suppression des tatweel
    $text = str_replace('ـ', '', $text);

    return trim($text);
}
}