<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>

<meta charset="UTF-8">

<style>

body{
    font-family: DejaVu Sans, sans-serif;
    font-size:14px;
    direction:rtl;
    margin:0;
    padding:0;
}

/* ========================================================= */
/* EN-TÊTE */
/* ========================================================= */

.header{
    width:100%;
    margin-bottom:25px;
    overflow:hidden;
}

.logo-container{
    float:left;
    width:20%;
    text-align:left;
}

.logo{
    width:100px;
}

.header-text{
    float:right;
    width:75%;
    text-align:right;
    direction:rtl;
    line-height:1.8;
}

.header-text p{
    margin:0;
    font-size:18px;
    font-weight:bold;
}

/* ========================================================= */
/* TITRES */
/* ========================================================= */

.title{
    text-align:center;
    font-weight:bold;
    margin-top:10px;
    margin-bottom:20px;
}

.title h2{
    margin:0 0 10px 0;
    font-size:20px;
}

.title h3{
    margin:7px 0;
    font-size:16px;
}

.title .right-title{
    text-align:right;
    direction:rtl;
}

/* ========================================================= */
/* TABLEAU */
/* ========================================================= */

table{
    width:100%;
    border-collapse:collapse;
    margin-top:20px;
}

table,
th,
td{
    border:1px solid #000;
}

th,
td{
    padding:8px;
    font-size:13px;
    vertical-align:middle;
}

thead{
    display:table-header-group;
}

thead th{
    background:#d9d9d9;
    text-align:center;
    font-weight:bold;
}

tbody{
    display:table-row-group;
}

tr{
    page-break-inside:avoid;
    break-inside:avoid;
}

.designation{
    text-align:right;
    direction:rtl;
}

.price{
    text-align:center;
}

.price-s{
    background:#d9ead3;
    text-align:center;
    font-weight:bold;
}

.total-row{
    background:#d9d9d9;
    font-weight:bold;
}

.total-row th{
    font-weight:bold;
    text-align:right;
}

.total-row th:last-child{
    text-align:center;
}

/* ========================================================= */
/* SIGNATURE */
/* ========================================================= */

.signature{
    margin-top:35px;
    text-align:right;
    direction:rtl;
}

.signature p{
    margin:0;
}

.signature h3{
    margin-top:25px;
    margin-bottom:25px;
}

/* ========================================================= */
/* PAGINATION */
/* ========================================================= */

/*
|--------------------------------------------------------------------------
| IMPORTANT :
| Chaque bloc .page correspond à UNE page PDF.
|--------------------------------------------------------------------------
*/

.page{
    page-break-after:always;
    break-after:page;

    page-break-inside:avoid;
    break-inside:avoid;
}

.page:last-child{
    page-break-after:auto;
    break-after:auto;
}

.page-number{
    text-align:center;
    margin-top:15px;
    font-weight:bold;
    font-size:13px;
}

</style>

</head>


<body>


{{-- ========================================================= --}}
{{-- PARCOURS DES FOURNISSEURS --}}
{{-- ========================================================= --}}

@foreach($repartition as $rep)


    @php

        /*
        |---------------------------------------------------------
        | Nombre EXACT d'articles par page
        |---------------------------------------------------------
        */

        $articlesParPage = 10;


        /*
        |---------------------------------------------------------
        | Découper les articles de CE fournisseur
        | en groupes de 10
        |---------------------------------------------------------
        */

        $pages = array_chunk(
            $rep['articles'],
            $articlesParPage
        );


        /*
        |---------------------------------------------------------
        | Nombre total de pages pour CE fournisseur
        |---------------------------------------------------------
        */

        $totalPages = max(1, count($pages));

    @endphp


    {{-- ========================================================= --}}
    {{-- PAGES DU FOURNISSEUR --}}
    {{-- ========================================================= --}}

    @foreach($pages as $pageIndex => $articlesPage)


        @php

            /*
            |-----------------------------------------------------
            | Numéro de la page actuelle
            |-----------------------------------------------------
            */

            $currentPage = $pageIndex + 1;


            /*
            |-----------------------------------------------------
            | Vérifier s'il existe une description
            | sur cette page
            |-----------------------------------------------------
            */

            $hasDescription = false;

            foreach($articlesPage as $articleCheck){

                if(
                    trim(
                        $articleCheck['description'] ?? ''
                    ) !== ''
                ){

                    $hasDescription = true;

                    break;

                }

            }

        @endphp


        {{-- ===================================================== --}}
        {{-- PAGE --}}
        {{-- ===================================================== --}}

        <div class="page">


            {{-- ================================================= --}}
            {{-- EN-TÊTE --}}
            {{-- ================================================= --}}

            <div class="header">


                {{-- LOGO --}}

                <div class="logo-container">

                    @php

                        $image = base64_encode(
                            file_get_contents(
                                public_path('images/f.jpg')
                            )
                        );

                    @endphp


                    <img
                        src="data:image/jpeg;base64,{{ $image }}"
                        class="logo"
                        alt="Logo FSG"
                    >

                </div>


                {{-- TEXTE ADMINISTRATIF --}}

                <div class="header-text">

                    <p>
                        الجمهورية التونسية
                    </p>

                    <p>
                        وزارة التعليم العالي والبحث العلمي
                    </p>

                    <p>
                        جامعة قابس
                    </p>

                    <p>
                        كلية العلوم بقابس
                    </p>

                </div>


            </div>


            <div style="clear:both;"></div>


            {{-- ================================================= --}}
            {{-- TITRE --}}
            {{-- ================================================= --}}

            <div class="title">


                <h2>

                    إستشارة عدد
                    {{ $consultation->reference }}

                </h2>


                <h3>

                    {{ $consultation->objet }}

                </h3>


                <h3 class="right-title">

                    قائمة توزيع الفصول حسب المزود

                </h3>


                <h3 class="right-title">

                    المزود :
                    {{ $rep['fournisseur']->name }}

                </h3>


            </div>


            {{-- ================================================= --}}
            {{-- TABLEAU --}}
            {{-- ================================================= --}}

            <table>


                <thead>

                <tr>


                    {{-- NUMÉRO --}}

                    <th width="7%">

                        ع/ر

                    </th>


                    {{-- DÉSIGNATION --}}

                    <th
                        width="{{ $hasDescription ? '27%' : '35%' }}"
                    >

                        بيان المطلوب

                    </th>


                    {{-- DESCRIPTION --}}

                    @if($hasDescription)

                        <th width="23%">

                            الوصف

                        </th>

                    @endif


                    {{-- QUANTITÉ --}}

                    <th
                        width="{{ $hasDescription ? '10%' : '12%' }}"
                    >

                        الكمية

                    </th>


                    {{-- PRIX MINIMUM --}}

                    <th
                        width="{{ $hasDescription ? '16%' : '23%' }}"
                    >

                        السعر الفردي الأدنى باعتبار كل الأداء

                    </th>


                    {{-- TOTAL --}}

                    <th
                        width="{{ $hasDescription ? '17%' : '23%' }}"
                    >

                        السعر الجملي باعتبار كل الأداء

                    </th>


                </tr>

                </thead>


                <tbody>


                {{-- ================================================= --}}
                {{-- ARTICLES --}}
                {{-- ================================================= --}}

                @foreach($articlesPage as $article)


                    @php

                        /*
                        |-------------------------------------------------
                        | Numéro global de l'article
                        |-------------------------------------------------
                        */

                        $articleNumber =
                            ($pageIndex * $articlesParPage)
                            + $loop->iteration;

                    @endphp


                    <tr>


                        {{-- NUMÉRO --}}

                        <td style="text-align:center;">

                            {{ $articleNumber }}

                        </td>


                        {{-- DÉSIGNATION --}}

                        <td class="designation">

                            {{ $article['designation'] }}

                        </td>


                        {{-- DESCRIPTION --}}

                        @if($hasDescription)

                            <td class="designation">

                                {{ $article['description'] ?? '' }}

                            </td>

                        @endif


                        {{-- QUANTITÉ --}}

                        <td style="text-align:center;">

                            {{ number_format(
                                (float)$article['quantite'],
                                3,
                                '.',
                                ''
                            ) }}

                        </td>


                        {{-- PRIX MINIMUM --}}

                        <td class="price-s">

                            {{ number_format(
                                (float)$article['prix'],
                                3,
                                '.',
                                ''
                            ) }}

                        </td>


                        {{-- TOTAL --}}

                        <td class="price">

                            {{ number_format(
                                (float)$article['total'],
                                3,
                                '.',
                                ''
                            ) }}

                        </td>


                    </tr>


                @endforeach


                {{-- ================================================= --}}
                {{-- TOTAL FOURNISSEUR --}}
                {{-- UNIQUEMENT SUR LA DERNIÈRE PAGE --}}
                {{-- ================================================= --}}

                @if($currentPage == $totalPages)


                    <tr class="total-row">


                        <th
                            colspan="{{ $hasDescription ? 5 : 4 }}"
                        >

                            المجموع باعتبار كل الأداء

                        </th>


                        <th>

                            {{ number_format(
                                (float)$rep['total'],
                                3,
                                '.',
                                ''
                            ) }}

                        </th>


                    </tr>


                @endif


                </tbody>


            </table>


            {{-- ================================================= --}}
            {{-- SIGNATURE --}}
            {{-- UNIQUEMENT SUR LA DERNIÈRE PAGE --}}
            {{-- ================================================= --}}

            @if($currentPage == $totalPages)


                <table
                    style="
                        width:100%;
                        border:none;
                        direction:ltr;
                    "
                >

                    <tr>


                        {{-- DOYEN À GAUCHE --}}

                        <td
                            style="
                                width:50%;
                                border:none;
                                text-align:left;
                                direction:rtl;
                                vertical-align:top;
                            "
                        >


                            <strong>

                                قابس في :
                                {{ date('d/m/Y') }}

                            </strong>


                            <br><br>


                            <strong>

                                العميــــد

                            </strong>


                            <br><br>


                            @if($doyen)

                                <strong>

                                    {{ $doyen->nom }}

                                </strong>

                            @else

                                <strong>

                                    ........................

                                </strong>

                            @endif


                        </td>


                        {{-- CÔTÉ DROIT VIDE --}}

                        <td
                            style="
                                width:50%;
                                border:none;
                            "
                        >
                        </td>


                    </tr>

                </table>


            @endif


            {{-- ================================================= --}}
            {{-- NUMÉRO DE PAGE --}}
            {{-- ================================================= --}}

            <div class="page-number">

                {{ $currentPage }}/{{ $totalPages }}

            </div>


        </div>


    @endforeach


@endforeach


</body>

</html>