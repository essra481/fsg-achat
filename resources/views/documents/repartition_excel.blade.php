<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>

<meta charset="UTF-8">

<style>

/* =========================================================
   GENERAL
   ========================================================= */

body {
    font-family: Arial, "DejaVu Sans", sans-serif;
    font-size: 11pt;
    margin: 0;
    padding: 0;
}


/* =========================================================
   EN-TÊTE ADMINISTRATIF
   TEXTE À DROITE + LOGO À GAUCHE
   ========================================================= */

.header-table {
    width: 100%;
    border-collapse: collapse;
    border: none;

    direction: ltr;

    margin: 0;
    padding: 0;
}

.header-table td {
    border: none;
    padding: 0;
}


/* =========================================================
   TEXTE ADMINISTRATIF
   ========================================================= */

.administration {

    direction: rtl;

    text-align: right !important;

    vertical-align: top;

    font-family: Arial, "DejaVu Sans", sans-serif;

    font-size: 12pt;

    font-weight: normal;

    line-height: 1.35;

    padding-top: 4px;
    padding-right: 8px;
    padding-bottom: 0;
    padding-left: 0;

    white-space: nowrap;

    width: 30%;
}


/* =========================================================
   ESPACE CENTRAL
   ========================================================= */

.header-middle {

    width: 50%;

    padding: 0;

    border: none;

}


/* =========================================================
   LOGO
   ========================================================= */

.logo-space {

    width: 20%;

    text-align: left;

    vertical-align: top;

    padding: 0;

    border: none;

}


/* =========================================================
   TABLEAU PRINCIPAL
   ========================================================= */

.main-table {

    width: 100%;

    border-collapse: collapse;

    direction: rtl;

    margin-top: 5px;

}


/* =========================================================
   CELLULES PRINCIPALES
   ========================================================= */

.main-table th,
.main-table td {

    font-family: Arial, "DejaVu Sans", sans-serif;

    font-size: 11pt;

    font-weight: normal;

    vertical-align: middle;

}


/* =========================================================
   TITRE
   ========================================================= */

.title {

    direction: rtl;

    text-align: center;

    font-size: 14pt;

    font-weight: bold !important;

    padding: 7px 0;

    border: none;

}


/* =========================================================
   OBJET
   ========================================================= */

.objet {

    direction: rtl;

    text-align: center;

    font-size: 12pt;

    font-weight: normal;

    padding: 7px 0;

    border: none;

}


/* =========================================================
   SOUS-TITRE
   ========================================================= */

.subtitle {

    direction: rtl;

    text-align: center;

    font-size: 13pt;

    font-weight: normal;

    padding: 7px 0 10px 0;

    border: none;

}


/* =========================================================
   FOURNISSEUR
   ========================================================= */

.fournisseur {

    direction: rtl;

    text-align: right;

    font-size: 11pt;

    font-weight: normal;

    padding: 7px 5px;

    border-top: 1px solid #cccccc;

    border-bottom: 1px solid #cccccc;

}


/* =========================================================
   EN-TÊTE DES COLONNES
   ========================================================= */

.table-header th {

    background-color: #dce6f1;

    direction: rtl;

    text-align: center;

    vertical-align: middle;

    font-size: 10pt;

    font-weight: normal;

    padding: 7px 5px;

    border: 1px solid #dddddd;

}


/* =========================================================
   LIGNES DES ARTICLES
   ========================================================= */

.article-row td {

    font-size: 10pt;

    font-weight: normal;

    padding: 6px 5px;

    vertical-align: middle;

    border: 1px solid #eeeeee;

}


/* =========================================================
   NUMÉRO
   ========================================================= */

.numero {

    direction: ltr;

    text-align: center;

    width: 7%;

}


/* =========================================================
   DÉSIGNATION
   ========================================================= */

.designation {

    direction: rtl;

    text-align: right;

    width: 25%;

}


/* =========================================================
   DESCRIPTION
   ========================================================= */

.description {

    direction: rtl;

    text-align: right;

    width: 20%;

}


/* =========================================================
   QUANTITÉ
   ========================================================= */

.quantite {

    direction: ltr;

    text-align: center;

    width: 10%;

}


/* =========================================================
   PRIX
   ========================================================= */

.prix {

    direction: ltr;

    text-align: center;

    width: 19%;

}


/* =========================================================
   PRIX MINIMUM
   ========================================================= */

.prix-minimum {

    background-color: #d9ead3;

    direction: ltr;

    text-align: center;

    font-weight: bold;

}


/* =========================================================
   TOTAL FOURNISSEUR
   ========================================================= */

.total-row td {

    background-color: #eef3f8;

    font-size: 10pt;

    font-weight: normal;

    padding: 7px 5px;

    border: 1px solid #dddddd;

}


/* =========================================================
   TEXTE DU TOTAL
   ========================================================= */

.total-label {

    direction: rtl !important;

    text-align: right !important;

    font-weight: normal !important;

}


/* =========================================================
   VALEUR DU TOTAL
   ========================================================= */

.total-value {

    direction: ltr !important;

    text-align: right !important;

    font-weight: normal !important;

}


/* =========================================================
   TABLEAU DES ÉGALITÉS
   ========================================================= */

.equalite-title {

    direction: rtl;

    text-align: right;

    font-size: 12pt;

    font-weight: bold;

    padding-top: 20px;

    padding-bottom: 10px;

}


.equalite-table {

    width: 100%;

    border-collapse: collapse;

    direction: rtl;

}


.equalite-table th {

    background-color: #eeeeee;

    border: 1px solid #cccccc;

    padding: 7px;

    font-weight: normal;

    text-align: center;

}


.equalite-table td {

    border: 1px solid #cccccc;

    padding: 7px;

    font-weight: normal;

}


.equalite-article {

    direction: rtl;

    text-align: right;

}


.equalite-fournisseurs {

    direction: rtl;

    text-align: right;

}


.equalite-price {

    direction: ltr;

    text-align: center;

    font-weight: bold;

}


/* =========================================================
   SIGNATURE DU DOYEN
   ========================================================= */

.signature-table {
    width: 100%;
    border-collapse: collapse;
    direction: ltr;
    margin-top: 30px;
    border: none !important;
}

.signature-table tr,
.signature-table td {
    border: none !important;
}

.signature-table td {
    border: none !important;
    font-family: Arial, "DejaVu Sans", sans-serif;
    font-size: 11pt;
    padding: 5px;
    vertical-align: top;
}

.signature-dean {
    width: 50%;
    text-align: left;
    direction: rtl;
    line-height: 1.8;
}

.signature-date {
    width: 50%;
    text-align: left;
    direction: rtl;
}

</style>

</head>


<body>


@php

    /*
    |--------------------------------------------------------------------------
    | Vérifier s'il existe au moins une description
    |--------------------------------------------------------------------------
    */

    $hasDescription = false;

    foreach($repartition as $rep){

        foreach($rep['articles'] as $article){

            if(
                trim(
                    $article['description'] ?? ''
                ) !== ''
            ){

                $hasDescription = true;

                break 2;

            }

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Nombre de colonnes
    |--------------------------------------------------------------------------
    */

    $colspan = $hasDescription ? 6 : 5;

@endphp


<!-- =========================================================
     EN-TÊTE
     ========================================================= -->

<table class="header-table">

    <tr>


        <!-- =================================================
             TEXTE ADMINISTRATIF
             ================================================= -->

        <td
            colspan="2"
            class="administration"
        >

            الجمهورية التونسية<br>

            وزارة التعليم العالي والبحث العلمي<br>

            جامعة قابس<br>

            كلية العلوم بقابس

        </td>



        <!-- =================================================
             ESPACE CENTRAL
             ================================================= -->

        <td
            colspan="2"
            class="header-middle"
        >
        </td>



        <!-- =================================================
             LOGO
             ================================================= -->

        <td class="logo-space">

            {{-- Le logo peut être ajouté par ConsultationExport
                 avec WithDrawings --}}

        </td>


    </tr>

</table>



<!-- =========================================================
     TABLEAU PRINCIPAL
     ========================================================= -->

<table class="main-table">


    <!-- =====================================================
         TITRE
         ===================================================== -->

    <tr>

        <td
            colspan="{{ $colspan }}"
            class="title"
        >

            إستشارة عدد
            {{ $consultation->reference }}

        </td>

    </tr>



    <!-- =====================================================
         OBJET
         ===================================================== -->

    <tr>

        <td
            colspan="{{ $colspan }}"
            class="objet"
        >

            {{ $consultation->objet }}

        </td>

    </tr>



    <!-- =====================================================
         SOUS-TITRE
         ===================================================== -->

    <tr>

        <td
            colspan="{{ $colspan }}"
            class="subtitle"
        >

            قائمة توزيع الفصول حسب المزود

        </td>

    </tr>



    <!-- =====================================================
         FOURNISSEURS
         ===================================================== -->

    @foreach($repartition as $rep)


        <!-- =================================================
             FOURNISSEUR
             ================================================= -->

        <tr>

            <td
                colspan="{{ $colspan }}"
                class="fournisseur"
            >

                المزود :
                {{ $rep['fournisseur']->name ?? '' }}

            </td>

        </tr>



        <!-- =================================================
             EN-TÊTE DES COLONNES
             ================================================= -->

        <tr class="table-header">


            <!-- NUMÉRO -->

            <th class="numero">

                ع/ر

            </th>



            <!-- DÉSIGNATION -->

            <th class="designation">

                بيان المشتريات

            </th>



            <!-- DESCRIPTION -->

            @if($hasDescription)

                <th class="description">

                    الوصف

                </th>

            @endif



            <!-- QUANTITÉ -->

            <th class="quantite">

                الكمية

            </th>



            <!-- PRIX MINIMUM -->

            <th class="prix">

                السعر الفردي الأدنى باعتبار كل الأداء

            </th>



            <!-- TOTAL -->

            <th class="prix">

                السعر الجملي باعتبار كل الأداء

            </th>


        </tr>



        <!-- =================================================
             ARTICLES
             ================================================= -->

        @php

            $numero = 1;

        @endphp



        @foreach($rep['articles'] as $article)


            <tr class="article-row">


                <!-- NUMÉRO -->

                <td class="numero">

                    {{ $numero++ }}

                </td>



                <!-- DÉSIGNATION -->

                <td class="designation">

                    {{ $article['designation'] ?? '' }}

                </td>



                <!-- DESCRIPTION -->

                @if($hasDescription)

                    <td class="description">

                        {{ $article['description'] ?? '' }}

                    </td>

                @endif



                <!-- QUANTITÉ -->

                <td class="quantite">

                    {{ number_format(
                        (float)($article['quantite'] ?? 0),
                        3,
                        '.',
                        ''
                    ) }}

                </td>



                <!-- PRIX MINIMUM -->

                <td class="prix-minimum">

                    {{ number_format(
                        (float)($article['prix'] ?? 0),
                        3,
                        '.',
                        ''
                    ) }}

                </td>



                <!-- TOTAL -->

                <td class="prix">

                    {{ number_format(
                        (float)($article['total'] ?? 0),
                        3,
                        '.',
                        ''
                    ) }}

                </td>


            </tr>


        @endforeach



        <!-- =================================================
             TOTAL FOURNISSEUR
             ================================================= -->

        <tr class="total-row">


            <td
                colspan="{{ $hasDescription ? 5 : 4 }}"
                class="total-label"
            >

                المجموع باعتبار كل الأداء

            </td>



            <td class="total-value">

                {{ number_format(
                    (float)($rep['total'] ?? 0),
                    3,
                    '.',
                    ''
                ) }}

            </td>


        </tr>


    @endforeach


</table>




</body>

</html>