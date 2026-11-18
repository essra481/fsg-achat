<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>

<meta charset="UTF-8">

<style>

@font-face{
    font-family: "DejaVu";
    src: local("DejaVu Sans");
}

body{
    font-family: DejaVu, sans-serif;
    direction: rtl;
    font-size:14px;
}

table{
    width:100%;
    border-collapse:collapse;
    margin-top:15px;
}

th,
td{
    border:1px solid black;
    padding:6px;
    text-align:center;
    vertical-align:middle;
}

.title{
    text-align:center;
    font-size:22px;
    font-weight:bold;
    margin-bottom:20px;
}

.info{
    width:100%;
    margin-bottom:15px;
}

.info td{
    border:none;
    text-align:right;
    padding:4px;
}

.green{
    background:#c6efce;
    font-weight:bold;
}

.gray{
    background:#f2f2f2;
}

.signature{
    margin-top:60px;
}

/*
|--------------------------------------------------------------------------
| PAGINATION
|--------------------------------------------------------------------------
*/

.page{
    page-break-after:always;
    page-break-inside:avoid;
}

.page:last-child{
    page-break-after:auto;
}



</style>

</head>


<body>

@php

/*
|--------------------------------------------------------------------------
| Nombre maximum d'articles par page
|--------------------------------------------------------------------------
*/

$articlesParPage = 8;


/*
|--------------------------------------------------------------------------
| Découper les articles
|--------------------------------------------------------------------------
*/

$pages = $consultation->articles->chunk($articlesParPage);

$totalPages = max(1, $pages->count());


/*
|--------------------------------------------------------------------------
| Vérifier s'il existe au moins une description
|--------------------------------------------------------------------------
*/

$hasDescription = $consultation->articles->contains(function ($article) {

    return !empty(
        trim($article->description ?? '')
    );

});


/*
|--------------------------------------------------------------------------
| Fournisseurs
|--------------------------------------------------------------------------
*/

$fournisseurs = $consultation->offres
    ->groupBy('fournisseur_id');


/*
|--------------------------------------------------------------------------
| Déterminer le fournisseur ayant obtenu
| le plus de prix minimums
|--------------------------------------------------------------------------
*/

$winner = [];

foreach($consultation->articles as $article){

    $prixArticle = [];

    foreach($fournisseurs as $fid => $offres){

        $offre = $offres
            ->where('article_id', $article->id)
            ->first();

        if(
            $offre &&
            $offre->prix_unitaire_ht !== null
        ){

            $prixArticle[$fid] =
                (float) $offre->prix_unitaire_ht;

        }

    }

    if(count($prixArticle) > 0){

        $minimum = min($prixArticle);

        foreach($prixArticle as $fid => $prix){

            if(
                (float) $prix ==
                (float) $minimum
            ){

                $winner[$fid] =
                    ($winner[$fid] ?? 0) + 1;

            }

        }

    }

}


$bestSupplier = null;

if(count($winner) > 0){

    $bestSupplier = array_search(
        max($winner),
        $winner
    );

}


/*
|--------------------------------------------------------------------------
| Calcul du total général
|--------------------------------------------------------------------------
*/

$grandTotalGeneral = 0;

foreach($consultation->articles as $article){

    $prix = [];

    foreach($fournisseurs as $fid => $offres){

        $offre = $offres
            ->where(
                'article_id',
                $article->id
            )
            ->first();

        if(
            $offre &&
            $offre->prix_unitaire_ht !== null
        ){

            $prix[$fid] =
                (float) $offre->prix_unitaire_ht;

        }

    }

    if(count($prix) > 0){

        $minimum = min($prix);

        $grandTotalGeneral +=
            $minimum *
            $article->quantite;

    }

}


/*
|--------------------------------------------------------------------------
| Signataires sélectionnés pour CE PV uniquement
|--------------------------------------------------------------------------
|
| IMPORTANT :
| On ne récupère plus tous les signataires actifs.
| On récupère uniquement ceux qui ont été cochés
| pour ce procès-verbal.
|--------------------------------------------------------------------------
*/

$signataires = $procesverbal->signataires;


/*
|--------------------------------------------------------------------------
| Membres de la commission sélectionnés
|--------------------------------------------------------------------------
*/

$membresCommission = $signataires->filter(function ($signataire) {

    return $signataire->type === 'أعضاء لجنة الفرز';

});


/*
|--------------------------------------------------------------------------
| Doyen sélectionné
|--------------------------------------------------------------------------
*/

$doyen = $signataires->first(function ($signataire) {

    return $signataire->type === 'العميد';

});

@endphp


{{-- ========================================================= --}}
{{-- PAGES --}}
{{-- ========================================================= --}}

@foreach($pages as $pageIndex => $articlesPage)

{{-- ========================================================= --}}
{{-- FORCER UN NOUVEAU PDF PAGE POUR CHAQUE BLOC --}}
{{-- ========================================================= --}}

@if($pageIndex > 0)

    <div style="page-break-before:always;"></div>

@endif


@php

$currentPage = $pageIndex + 1;

@endphp


<div class="page">


{{-- ========================================================= --}}
{{-- HEADER : LOGO + INFORMATIONS ADMINISTRATIVES --}}
{{-- ========================================================= --}}

<div style="width:100%; overflow:hidden; margin-bottom:30px;">

    {{-- Logo --}}

    <div style="float:left; width:20%;">

        @php

            $image = base64_encode(
                file_get_contents(
                    public_path('images/f.jpg')
                )
            );

        @endphp

        <img
            src="data:image/jpeg;base64,{{ $image }}"
            style="width:100px;"
        >

    </div>


    {{-- Texte --}}

    <div
        style="
            float:right;
            width:75%;
            text-align:right;
            direction:rtl;
        "
    >

        <p style="margin:0;font-size:18px;font-weight:bold;">
            الجمهورية التونسية
        </p>

        <p style="margin:0;font-size:18px;font-weight:bold;">
            وزارة التعليم العالي والبحث العلمي
        </p>

        <p style="margin:0;font-size:18px;font-weight:bold;">
            جامعة قابس
        </p>

        <p style="margin:0;font-size:18px;font-weight:bold;">
            كلية العلوم بقابس
        </p>

    </div>

</div>



{{-- ========================================================= --}}
{{-- TITRE --}}
{{-- ========================================================= --}}

<div class="title">

    تقرير فتح و فرز عروض إستشارة عدد

    {{ $consultation->reference }}

</div>



{{-- ========================================================= --}}
{{-- INFORMATIONS CONSULTATION --}}
{{-- ========================================================= --}}

<table class="info">

    <tr>

        <td width="25%">
            <strong>الموضوع :</strong>
        </td>

        <td>
            {{ $consultation->objet }}
        </td>

    </tr>


    <tr>

        <td>
            <strong>تاريخ صدور الإستشارة :</strong>
        </td>

        <td>
            {{ $consultation->date_lancement }}
        </td>

    </tr>


    <tr>

        <td>
            <strong>عدد العروض :</strong>
        </td>

        <td>
            {{ $consultation->offres->groupBy('fournisseur_id')->count() }}
        </td>

    </tr>


    <tr>

        <td>
            <strong>المكان :</strong>
        </td>

        <td>
            كلية العلوم بقابس
        </td>

    </tr>

</table>



{{-- ========================================================= --}}
{{-- SIGNATAIRES : MEMBRES + DOYEN --}}
{{-- ========================================================= --}}

<p style="margin-top:20px;">

    <strong>
        أعضاء لجنة الفرز :
    </strong>

</p>


@if($membresCommission->count() > 0)

    <ul
        style="
            margin-top:5px;
            padding-right:25px;
        "
    >

        @foreach($membresCommission as $membre)

            <li style="margin-bottom:6px;">

                {{ $membre->nom }}

                :

                {{ $membre->fonction }}

            </li>

        @endforeach

    </ul>

@else

    <p>
        لا يوجد أعضاء لجنة فرز مسجلون.
    </p>

@endif



{{-- ========================================================= --}}
{{-- TEXTE INTRODUCTIF --}}
{{-- ========================================================= --}}

<p>

    اجتمعت لجنة فتح وفرز العروض لدراسة العروض المقدمة من المزودين وكانت النتائج كما يلي :

</p>



{{-- ========================================================= --}}
{{-- TABLEAU DES OFFRES --}}
{{-- ========================================================= --}}

<table>

<thead>

<tr>

    <th
        rowspan="2"
        class="gray"
    >
        ع/ر
    </th>


    <th
        rowspan="2"
        class="gray"

        @if($hasDescription)
            style="width:25%;"
        @endif
    >
        بيان الفصل
    </th>


    @if($hasDescription)

        <th
            rowspan="2"
            class="gray"
            style="width:20%;"
        >
            الوصف
        </th>

    @endif


    <th
        rowspan="2"
        class="gray"
    >
        الكمية
    </th>


    <th
        colspan="{{ $fournisseurs->count() }}"
        class="gray"
    >

        عروض المزودين : السعر الفردي

    </th>


    <th
        rowspan="2"
        class="gray"
    >

        السعر الفردي الأدنى

    </th>


    <th
        rowspan="2"
        class="gray"
    >

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

$i = ($pageIndex * $articlesParPage) + 1;

$grandTotalPage = 0;

@endphp



{{-- ========================================================= --}}
{{-- ARTICLES DE CETTE PAGE UNIQUEMENT --}}
{{-- ========================================================= --}}

@foreach($articlesPage as $article)


@php

/*
|--------------------------------------------------------------------------
| Prix de tous les fournisseurs pour cet article
|--------------------------------------------------------------------------
*/

$prix = [];

foreach($fournisseurs as $fid => $offres){

    $offre = $offres
        ->where(
            'article_id',
            $article->id
        )
        ->first();

    if(
        $offre &&
        $offre->prix_unitaire_ht !== null
    ){

        $prix[$fid] =
            (float) $offre->prix_unitaire_ht;

    }

}


/*
|--------------------------------------------------------------------------
| Minimum
|--------------------------------------------------------------------------
*/

$minimum = null;

if(count($prix) > 0){

    $minimum = min($prix);

}


/*
|--------------------------------------------------------------------------
| Total de la ligne
|--------------------------------------------------------------------------
*/

$total = null;

if($minimum !== null){

    $total =
        $minimum *
        $article->quantite;

    $grandTotalPage += $total;

}

@endphp



<tr>

    {{-- Numéro --}}

    <td>

        {{ $i++ }}

    </td>



    {{-- Désignation --}}

    <td style="text-align:right">

        {{ $article->designation }}

    </td>



    {{-- Description --}}

    @if($hasDescription)

        <td style="text-align:right">

            {{ $article->description ?? '' }}

        </td>

    @endif



    {{-- Quantité --}}

    <td>

        {{ $article->quantite }}

    </td>



    {{-- Prix des fournisseurs --}}

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
            ){

                if(
                    (float) $offre->prix_unitaire_ht
                    ==
                    (float) $minimum
                ){

                    $estMinimum = true;

                }

            }

        @endphp


        <td

            @if($estMinimum)

                class="green"

            @endif

        >

            @if(
                $offre &&
                $offre->prix_unitaire_ht !== null
            )

                {{ number_format(
                    $offre->prix_unitaire_ht,
                    3
                ) }}

            @else

                ND

            @endif

        </td>

    @endforeach



    {{-- Prix minimum --}}

    <td class="green">

        @if($minimum !== null)

            {{ number_format(
                $minimum,
                3
            ) }}

        @else

            ND

        @endif

    </td>



    {{-- Prix total --}}

    <td class="green">

        @if($total !== null)

            {{ number_format(
                $total,
                3
            ) }}

        @else

            ND

        @endif

    </td>


</tr>


@endforeach



{{-- ========================================================= --}}
{{-- TOTAL GENERAL : UNIQUEMENT DERNIERE PAGE --}}
{{-- ========================================================= --}}

@if($currentPage == $totalPages)

<tr>

    <th
        colspan="{{ 4 + $fournisseurs->count() + ($hasDescription ? 1 : 0) }}"
        style="background:#d9d9d9;"
    >

        المجموع

    </th>


    <th
        colspan="2"
        class="green"
    >

        {{ number_format(
            $grandTotalGeneral,
            3
        ) }}

    </th>

</tr>

@endif


</tbody>

</table>



{{-- ========================================================= --}}
{{-- COMMENTAIRES : UNIQUEMENT DERNIERE PAGE --}}
{{-- ========================================================= --}}

@if($currentPage == $totalPages)

    @if($procesverbal->commentaire)

        <h3 style="margin-top:35px">

            الملاحظات

        </h3>


        <p style="line-height:30px">

            {!! nl2br(
                e($procesverbal->commentaire)
            ) !!}

        </p>

    @endif



    {{-- ========================================================= --}}
    {{-- SIGNATURES --}}
    {{-- ========================================================= --}}

    <table
        class="signature"
        style="border:none;"
    >

    <tr>

        {{-- ===================================================== --}}
        {{-- MEMBRES COMMISSION --}}
        {{-- ===================================================== --}}

        <td
            style="
                border:none;
                text-align:right;
                width:50%;
                vertical-align:top;
            "
        >

            @if($membresCommission->count() > 0)

                @foreach($membresCommission as $membre)

                    <p style="margin-bottom:18px;">

                        {{ $membre->nom }}

                        :

                        {{ $membre->fonction }}

                        <br>

                        .............................................

                    </p>

                @endforeach

            @else

                <p>
                    .............................................
                </p>

            @endif

        </td>



        {{-- ===================================================== --}}
        {{-- DOYEN --}}
        {{-- ===================================================== --}}

        <td
            style="
                border:none;
                text-align:center;
                width:50%;
                vertical-align:top;
            "
        >

            <p>

                قابس في :

                {{ date('d/m/Y') }}

            </p>


            <br>


            <strong>

                العميــد

            </strong>


            <br><br>


            <strong>

                {{ $doyen?->nom ?? '........................' }}

            </strong>

        </td>

    </tr>

    </table>

@endif






</div>

@endforeach


</body>

</html>