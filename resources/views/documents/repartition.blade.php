@extends('layouts.app')

@section('content')

<div class="container">

    {{-- ========================================================= --}}
    {{--                    RÉPARTITIONS                          --}}
    {{-- ========================================================= --}}

    @foreach($repartition as $rep)

        @php
            $hasDescription = collect($rep['articles'])
                ->contains(function ($article) {
                    return trim($article['description'] ?? '') !== '';
                });
        @endphp


        <div class="border rounded p-4 mb-5">

            {{-- ================================================= --}}
            {{-- EN-TÊTE                                          --}}
            {{-- ================================================= --}}

            <table
                width="100%"
                style="
                    border:none;
                    width:100%;
                    margin-bottom:30px;
                "
            >

                <tr>

                    {{-- LOGO --}}
                    <td
                        width="25%"
                        style="
                            border:none;
                            text-align:left;
                            vertical-align:top;
                        "
                    >

                        <img
                            src="{{ asset('images/fs.png') }}"
                            width="120"
                            alt="Logo FSG"
                        >

                    </td>


                    {{-- TEXTE ADMINISTRATIF --}}
                    <td
                        width="75%"
                        style="
                            border:none;
                            text-align:right;
                            direction:rtl;
                            vertical-align:top;
                            line-height:1.8;
                        "
                    >

                        <strong>

                            <p style="margin:0;">
                                الجمهورية التونسية
                            </p>

                            <p style="margin:0;">
                                وزارة التعليم العالي والبحث العلمي
                            </p>

                            <p style="margin:0;">
                                جامعة قابس
                            </p>

                            <p style="margin:0;">
                                كلية العلوم بقابس
                            </p>

                        </strong>

                    </td>

                </tr>

            </table>


            {{-- ================================================= --}}
            {{-- TITRE                                             --}}
            {{-- ================================================= --}}

            <div
                style="
                    text-align:right;
                    direction:rtl;
                "
            >

                <h4>
                    إستشارة عدد
                    {{ $consultation->reference }}
                </h4>


                <h5>
                    {{ $consultation->objet }}
                </h5>


                <h4 style="margin-top:25px;">
                    قائمة توزيع الفصول حسب المزود
                </h4>


                <h5>
                    المزود :
                    {{ $rep['fournisseur']->name }}
                </h5>

            </div>


            <br>


            {{-- ================================================= --}}
            {{-- TABLEAU                                           --}}
            {{-- ================================================= --}}

            <table
                class="table table-bordered text-center"
                style="
                    width:100%;
                    margin-bottom:0;
                "
                dir="rtl"
            >

                <thead>

                    <tr>

                        <th>
                            ع/ر
                        </th>


                        @if($hasDescription)

                            <th style="width:27%;">
                                بيان المطلوب
                            </th>

                            <th style="width:23%;">
                                الوصف
                            </th>

                            <th style="width:10%;">
                                الكمية
                            </th>

                            <th style="width:20%;">
                                السعر الفردي الأدنى باعتبار كل الأداء
                            </th>

                            <th style="width:20%;">
                                السعر الجملي باعتبار كل الأداء
                            </th>

                        @else

                            <th style="width:35%;">
                                بيان المطلوب
                            </th>

                            <th style="width:12%;">
                                الكمية
                            </th>

                            <th style="width:26%;">
                                السعر الفردي الأدنى باعتبار كل الأداء
                            </th>

                            <th style="width:27%;">
                                السعر الجملي باعتبار كل الأداء
                            </th>

                        @endif

                    </tr>

                </thead>


                <tbody>

                    @php
                        $i = 1;
                    @endphp


                    @foreach($rep['articles'] as $article)

                        <tr>

                            {{-- NUMÉRO --}}
                            <td style="text-align:center;">

                                {{ $i++ }}

                            </td>


                            {{-- DÉSIGNATION --}}
                            <td
                                style="
                                    text-align:right;
                                    direction:rtl;
                                "
                            >

                                {{ $article['designation'] }}

                            </td>


                            {{-- DESCRIPTION --}}
                            @if($hasDescription)

                                <td
                                    style="
                                        text-align:right;
                                        direction:rtl;
                                    "
                                >

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
                            <td
                                style="
                                    text-align:center;
                                    background:#d9ead3;
                                    font-weight:bold;
                                "
                            >

                                {{ number_format(
                                    (float)$article['prix'],
                                    3,
                                    '.',
                                    ''
                                ) }}

                            </td>


                            {{-- TOTAL --}}
                            <td style="text-align:center;">

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
                    {{-- TOTAL FOURNISSEUR                                --}}
                    {{-- ================================================= --}}

                    <tr
                        style="
                            background:#d9d9d9;
                            font-weight:bold;
                        "
                    >

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

                </tbody>

            </table>


           
{{-- ================================================= --}}
{{-- SIGNATURE                                         --}}
{{-- ================================================= --}}

<br>
<br>
<br>

<table
    style="
        width:100%;
        border:none;
        direction:ltr;"
>
    <tr>

        <td
            style="
                width:50%;
                border:none;
                text-align:left;
                direction:rtl;
                vertical-align:top;
            "
        >

            قابس في :
            {{ date('d/m/Y') }}

            <br><br>

            <strong>
                العميــــد
            </strong>

            <br><br>

            <strong>
                {{ $doyen?->nom ?? '........................' }}
            </strong>

        </td>

        <td
            style="
                width:50%;
                border:none;
            "
        ></td>

    </tr>
</table>




        {{-- SAUT DE PAGE ENTRE LES FOURNISSEURS --}}
        @if(!$loop->last)

            <div
                style="
                    page-break-after:always;
                "
            ></div>

        @endif

    @endforeach



    {{-- ========================================================= --}}
    {{--              REMARQUES D'ÉGALITÉ                         --}}
    {{-- ========================================================= --}}

    @if(!empty($egalites))

        <div
            style="
                margin-top:35px;
                padding:18px;
                direction:rtl;
                text-align:right;
            "
        >

            <h4
                style="
                    margin-top:0;
                    margin-bottom:20px;
                    font-weight:bold;
                "
            >

                ملاحظات حول تساوي الأسعار الدنيا

            </h4>


            {{-- ================================================= --}}
            {{-- TABLEAU DES ÉGALITÉS                              --}}
            {{-- ================================================= --}}

            <table
                style="
                    width:100%;
                    border-collapse:collapse;
                    direction:rtl;
                    text-align:center;
                "
            >

                <thead>

                    <tr
                        style="
                            background:#eeeeee;
                            font-weight:bold;
                        "
                    >

                        {{-- NUMÉRO --}}
                        <th
                            style="
                                border:1px solid #cccccc;
                                padding:10px;
                                width:8%;
                            "
                        >

                            ع/ر

                        </th>


                        {{-- ARTICLE --}}
                        <th
                            style="
                                border:1px solid #cccccc;
                                padding:10px;
                                width:32%;
                                text-align:right;
                            "
                        >

                            الفصل

                        </th>


                        {{-- FOURNISSEURS --}}
                        <th
                            style="
                                border:1px solid #cccccc;
                                padding:10px;
                                width:40%;
                                text-align:right;
                            "
                        >

                            المزودون الذين لهم نفس السعر

                        </th>


                        {{-- PRIX --}}
                        <th
                            style="
                                border:1px solid #cccccc;
                                padding:10px;
                                width:20%;
                            "
                        >

                            السعر الأدنى

                        </th>

                    </tr>

                </thead>


                <tbody>

                    @foreach($egalites as $index => $egalite)

                        <tr>

                            {{-- NUMÉRO --}}
                            <td
                                style="
                                    border:1px solid #cccccc;
                                    padding:10px;
                                    text-align:center;
                                "
                            >

                                {{ $index + 1 }}

                            </td>


                            {{-- ARTICLE --}}
                            <td
                                style="
                                    border:1px solid #cccccc;
                                    padding:10px;
                                    text-align:right;
                                    direction:rtl;
                                "
                            >

                                {{ $egalite['article'] }}

                            </td>


                            {{-- FOURNISSEURS --}}
                            <td
                                style="
                                    border:1px solid #cccccc;
                                    padding:10px;
                                    text-align:right;
                                    direction:rtl;
                                "
                            >

                                {{ $egalite['fournisseurs'] }}

                            </td>


                            {{-- PRIX --}}
                            <td
                                style="
                                    border:1px solid #cccccc;
                                    padding:10px;
                                    text-align:center;
                                    font-weight:bold;
                                "
                            >

                                {{ number_format(
                                    (float)$egalite['prix'],
                                    3,
                                    '.',
                                    ''
                                ) }}

                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

    @endif



    {{-- ========================================================= --}}
    {{--                        BOUTONS                            --}}
    {{-- ========================================================= --}}

    <div class="mt-4 mb-4">

        <a
            href="{{ route(
                'documents.pdf.form',
                $consultation->id
            ) }}"
            class="btn btn-danger"
        >

            <i class="fa-solid fa-file-pdf"></i>

            Générer PDF

        </a>
        <br><br>

        <div class="d-flex flex-wrap gap-2 mb-4">

    @foreach($repartition as $rep)

        @if($rep['fournisseur'])

            <a
                href="{{ route('documents.excel', [
                    'id' => $consultation->id,
                    'fournisseur_id' => $rep['fournisseur']->id
                ]) }}"
                class="btn btn-success"
            >
                <i class="fas fa-file-excel"></i>

                Excel -
                {{ $rep['fournisseur']->name }}

            </a>

        @endif

    @endforeach

</div>


        <a
            href="{{ route('document.index') }}"
            class="btn btn-secondary"
        >

            Retour

        </a>

    </div>

</div>

@endsection

