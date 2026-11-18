<!DOCTYPE html>

<html lang="ar" dir="rtl">

<head>

<meta charset="UTF-8">

<style>

    body {
        font-family: Arial, "DejaVu Sans", sans-serif;
        direction: rtl;
        font-size: 11px;
        color: #222;
        margin: 0;
        padding: 0;
    }

    table {
        border-collapse: collapse;
        width: 100%;
    }

    /* =====================================================
       EN-TETE ADMINISTRATIF
    ===================================================== */

    .header-table {
        width: 100%;
        border-collapse: collapse;
        border: none;
        margin: 0;
        padding: 0;
    }

    .header-table td {
        border: none !important;
        padding: 0 !important;
    }

    .header-text {
        width: 75%;
        text-align: right !important;
        vertical-align: top;
        direction: rtl;
        padding: 4px 0 0 15px !important;
        font-size: 12px;
        line-height: 1.9;
        white-space: nowrap;
    }

    .header-logo {
        width: 25%;
        text-align: left !important;
        vertical-align: top;
        padding: 0 !important;
    }

    /* =====================================================
       TITRE
    ===================================================== */

    .title-table {
        width: 100%;
        border: none;
        border-collapse: collapse;
        margin-top: 15px;
        margin-bottom: 15px;
    }

    .title-table td {
        border: none !important;
        font-size: 16px;
        font-weight: bold;
        padding: 8px;
        vertical-align: middle;
    }

    .title-text {
        width: 75%;
        text-align: right !important;
    }

    .title-empty {
        width: 25%;
    }

    /* =====================================================
       INFORMATIONS
    ===================================================== */

    .info-table {
        width: 100%;
        border: none;
        border-collapse: collapse;
        margin-bottom: 15px;
    }

    .info-table td {
        border: none !important;
        font-size: 12px;
        padding: 5px 8px;
        vertical-align: middle;
    }

    .info-label {
        width: 50%;
        text-align: right !important;
        font-weight: bold !important;
        white-space: nowrap;
    }

    .info-value {
        width: 75%;
        text-align: right !important;
        font-weight: normal;
    }

    /* =====================================================
       INTRODUCTION
    ===================================================== */

    .intro-table {
        width: 100%;
        border: none;
        border-collapse: collapse;
        margin-top: 8px;
        margin-bottom: 15px;
    }

    .intro-table td {
        border: none !important;
        text-align: right !important;
        padding: 4px 8px;
        font-size: 11px;
        line-height: 1.7;
    }

    .intro-text {
        width: 75%;
        text-align: right !important;
    }

    .intro-empty {
        width: 25%;
    }

    .intro-title {
        font-weight: bold;
        padding-top: 7px !important;
        padding-bottom: 7px !important;
    }

    /* =====================================================
       TABLEAU PRINCIPAL
    ===================================================== */

    .main-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }

    .main-table th,
    .main-table td {
        padding: 6px 8px;
        vertical-align: middle;
    }

    .main-table th {
        background-color: #f2f2f2;
        font-weight: bold;
        text-align: center;
        border: 1px solid #d9d9d9;
    }

    .main-table td {
        border: 1px solid #e0e0e0;
        text-align: center;
    }

    .main-table td.designation {
        text-align: right;
    }

    .main-table td.description {
        text-align: right;
    }

    /* =====================================================
       PRIX MINIMUM
    ===================================================== */

    .green-bg {
        background-color: #e2f0d9 !important;
        font-weight: bold;
    }

    /* =====================================================
       TOTAL GENERAL
    ===================================================== */

    .total-row td,
    .total-row th {
        background-color: #e2f0d9 !important;
        font-weight: bold;
        border: 1px solid #d9d9d9;
    }

    /* =====================================================
       COMMENTAIRE
    ===================================================== */

    .comment-title {
        text-align: right;
        font-weight: bold;
        font-size: 12px;
        margin-top: 15px;
        margin-bottom: 6px;
    }

    .comment-box {
        text-align: right;
        font-size: 11px;
        line-height: 1.7;
        padding: 5px;
    }

    /* =====================================================
       SIGNATURES
    ===================================================== */

    .signature-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 25px;
    }

    .signature-table td {
        border: none !important;
        text-align: center;
        vertical-align: top;
        padding: 7px;
        font-size: 11px;
        line-height: 1.8;
    }

</style>

</head>

<body>

@php

/*
|--------------------------------------------------------------------------
| DESCRIPTION
|--------------------------------------------------------------------------
*/

$hasDescription = $consultation->articles->contains(
    function ($article) {
        return trim($article->description ?? '') !== '';
    }
);


/*
|--------------------------------------------------------------------------
| FOURNISSEURS
|--------------------------------------------------------------------------
*/

$fournisseurs = $consultation->offres
    ->groupBy('fournisseur_id');


/*
|--------------------------------------------------------------------------
| MEMBRES DE LA COMMISSION
|--------------------------------------------------------------------------
*/

$membresCommission = \App\Models\Signataire::where(
    'type',
    'أعضاء لجنة الفرز'
)
->where('actif', true)
->orderBy('id')
->get();


/*
|--------------------------------------------------------------------------
| DOYEN
|--------------------------------------------------------------------------
*/

$doyen = \App\Models\Signataire::where(
    'type',
    'العميد'
)
->where('actif', true)
->first();


/*
|--------------------------------------------------------------------------
| NOMBRE DE COLONNES AVANT
| "السعر الأدنى" + "السعر الجملي"
|--------------------------------------------------------------------------
*/

$nbColonnesAvantTotal =
    1
    + 1
    + ($hasDescription ? 1 : 0)
    + 1
    + $fournisseurs->count();


@endphp

{{-- =========================================================
EN-TETE ADMINISTRATIF
========================================================= --}}

<table class="header-table">


<tr>

    <td class="header-text">

        الجمهورية التونسية<br>
        وزارة التعليم العالي والبحث العلمي<br>
        جامعة قابس<br>
        كلية العلوم بقابس

    </td>

</tr>

</table>

{{-- =========================================================
TITRE
========================================================= --}}

<table class="title-table">


<tr>

    <td class="title-text">

        تقرير فتح و فرز عروض إستشارة عدد

        {{ $consultation->reference }}

    </td>

    <td class="title-empty">
    </td>

</tr>


</table>

{{-- =========================================================
INFORMATIONS
========================================================= --}}

<table class="info-table">


<tr>

    <td class="info-label">
        الموضوع :
    </td>

    <td class="info-value">
        {{ $consultation->objet }}
    </td>

</tr>


<tr>

    <td class="info-label">
        تاريخ صدور الإستشارة :
    </td>

    <td class="info-value">
        {{ $consultation->date_lancement }}
    </td>

</tr>


<tr>

    <td class="info-label">
        عدد العروض :
    </td>

    <td class="info-value">
        {{ $fournisseurs->count() }}
    </td>

</tr>


</table>

{{-- =========================================================
COMMISSION
========================================================= --}}

<table class="intro-table">


<tr>

    <td class="intro-text">

        المكان: مكتب المصلحة المالية بكلية العلوم بقابس

    </td>

    <td class="intro-empty">
    </td>

</tr>


{{-- TITRE COMMISSION --}}

<tr>

    <td class="intro-text intro-title">

        أعضاء لجنة فرز:

    </td>

    <td class="intro-empty">
    </td>

</tr>


{{-- MEMBRES DYNAMIQUES --}}

@foreach($membresCommission as $membre)

    <tr>

        <td class="intro-text">

            {{ $membre->nom }} :
            {{ $membre->fonction }}

        </td>

        <td class="intro-empty">
        </td>

    </tr>

@endforeach


{{-- SI AUCUN MEMBRE --}}

@if($membresCommission->count() === 0)

    <tr>

        <td class="intro-text">

            Aucun membre de la commission enregistré.

        </td>

        <td class="intro-empty">
        </td>

    </tr>

@endif


{{-- INTRODUCTION --}}

<tr>

    <td class="intro-text intro-title">

        تم فتح العروض المقدمة من طرف العارضين والمبينة بالجدول التالي:

    </td>

    <td class="intro-empty">
    </td>

</tr>


</table>

{{-- =========================================================
TABLEAU PRINCIPAL
========================================================= --}}

<table class="main-table">


<thead>

    <tr>

        <th rowspan="2">

            ع/ر

        </th>


        <th rowspan="2">

            بيان الفصل

        </th>


        @if($hasDescription)

            <th rowspan="2">

                الوصف

            </th>

        @endif


        <th rowspan="2">

            الكمية

        </th>


        <th colspan="{{ $fournisseurs->count() }}">

            عروض المزودين

        </th>


        <th rowspan="2">

            السعر الأدنى

        </th>


        <th rowspan="2">

            السعر الجملي

        </th>

    </tr>



    <tr>

        @foreach($fournisseurs as $fid => $offres)

            <th>

                {{ $offres->first()->fournisseur->name }}

            </th>

        @endforeach

    </tr>

</thead>



<tbody>


    @php

        $grandTotal = 0;

    @endphp



    @foreach($consultation->articles as $article)


        @php

            /*
            |--------------------------------------------------------------------------
            | PRIX DES FOURNISSEURS
            |--------------------------------------------------------------------------
            */

            $prix = [];


            foreach($fournisseurs as $fid => $offres) {

                $offreTemp = $offres
                    ->where(
                        'article_id',
                        $article->id
                    )
                    ->first();


                if(
                    $offreTemp &&
                    $offreTemp->prix_unitaire_ht !== null
                ) {

                    $prix[$fid] =
                        (float) $offreTemp->prix_unitaire_ht;

                }

            }


            /*
            |--------------------------------------------------------------------------
            | MINIMUM
            |--------------------------------------------------------------------------
            */

            $minimum = null;


            if(count($prix) > 0) {

                $minimum = min($prix);

            }


            /*
            |--------------------------------------------------------------------------
            | TOTAL LIGNE
            |--------------------------------------------------------------------------
            */

            $total = null;


            if($minimum !== null) {

                $total =
                    $minimum *
                    (float) $article->quantite;


                $grandTotal += $total;

            }

        @endphp



        <tr>


            {{-- NUMERO --}}

            <td>

                {{ $loop->iteration }}

            </td>



            {{-- DESIGNATION --}}

            <td class="designation">

                {{ $article->designation }}

            </td>



            {{-- DESCRIPTION --}}

            @if($hasDescription)

                <td class="description">

                    {{ $article->description ?? '' }}

                </td>

            @endif



            {{-- QUANTITE --}}

            <td>

                {{ number_format(
                    (float) $article->quantite,
                    3,
                    '.',
                    ''
                ) }}

            </td>



            {{-- FOURNISSEURS --}}

            @foreach($fournisseurs as $fid => $offres)


                @php

                    $offre = $offres
                        ->where(
                            'article_id',
                            $article->id
                        )
                        ->first();


                    $estMinimum = false;


                    if(
                        $offre &&
                        $offre->prix_unitaire_ht !== null &&
                        $minimum !== null
                    ) {

                        $estMinimum =
                            (float) $offre->prix_unitaire_ht
                            ==
                            (float) $minimum;

                    }

                @endphp



                <td

                    @if($estMinimum)

                        class="green-bg"

                    @endif

                >


                    @if(
                        $offre &&
                        $offre->prix_unitaire_ht !== null
                    )

                        {{ number_format(
                            (float) $offre->prix_unitaire_ht,
                            3,
                            '.',
                            ''
                        ) }}

                    @else

                        ND

                    @endif


                </td>


            @endforeach



            {{-- PRIX MINIMUM --}}

            <td

                @if($minimum !== null)

                    class="green-bg"

                @endif

            >

                @if($minimum !== null)

                    {{ number_format(
                        (float) $minimum,
                        3,
                        '.',
                        ''
                    ) }}

                @else

                    ND

                @endif

            </td>



            {{-- PRIX TOTAL --}}

            <td class="green-bg">

                @if($total !== null)

                    {{ number_format(
                        (float) $total,
                        3,
                        '.',
                        ''
                    ) }}

                @else

                    ND

                @endif

            </td>


        </tr>

    @endforeach



    {{-- =================================================
         TOTAL GENERAL
    ================================================== --}}

    <tr class="total-row">


        <td colspan="{{ $nbColonnesAvantTotal }}"></td>


        <th>

            المجموع

        </th>


        <th>

            {{ number_format(
                (float) $grandTotal,
                3,
                '.',
                ''
            ) }}

        </th>


    </tr>


</tbody>


</table>

{{-- =========================================================
COMMENTAIRE
========================================================= --}}

@if(
isset($procesverbal) &&
$procesverbal &&
$procesverbal->commentaire
)


<div class="comment-title">

    الملاحظات

</div>


<div class="comment-box">

    {{ $procesverbal->commentaire }}

</div>


@endif

{{-- =========================================================
SIGNATURES
========================================================= --}}

<table class="signature-table">

    <tr>

        {{-- MEMBRES --}}
        <td style="width:40%; text-align:right; vertical-align:top;">

            <strong>
                الإمضاء
            </strong>

        </td>

        <td style="width:20%;"></td>

        {{-- DOYEN + DATE --}}
        <td style="
            width:40%;
            text-align:center;
            vertical-align:top;
        ">

            <strong>
                قابس في {{ date('d/m/Y') }}
            </strong>

            <br><br>

            <strong>
                العميـــد
            </strong>

            <br><br>

            <strong>
                {{ $doyen?->nom ?? '........................' }}
            </strong>

        </td>

    </tr>


    {{-- SIGNATURE DE CHAQUE MEMBRE --}}
    @foreach($membresCommission as $membre)

        <tr>

            <td style="text-align:right;">

                {{ $membre->nom }} :
                {{ $membre->fonction }}

            </td>

            <td>
                ....................................
            </td>

            <td></td>

        </tr>

    @endforeach


</table>

</body>

</html>
