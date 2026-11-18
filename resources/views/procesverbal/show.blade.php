@extends('layouts.app')

@section('content')

<div class="container">

    <div class="card shadow">

        <table width="100%" style="margin-bottom:30px;">

            <tr>

                <td width="25%" align="left">

                    <img src="{{ asset('images/f.jpg') }}" width="120">

                </td>

                <td width="75%" align="right" dir="rtl">

                    <p>الجمهورية التونسية</p>
                    <p>وزارة التعليم العالي و البحث العلمي</p>
                    <p>جامعة قابس</p>
                    <p>كلية العلوم بقابس</p>

                </td>

            </tr>

        </table>


        <div class="card-body">

            <h3 class="text-center mb-4" dir="rtl">

                تقرير فتح و فرز عروض إستشارة عدد :

                {{ $consultation->reference }}

            </h3>


            <table class="table table-borderless" dir="rtl">

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
                        مكتب المصلحة المالية بكلية العلوم بقابس
                    </td>

                </tr>

            </table>


            <hr>


            @php
    
    // Récupérer uniquement les signataires associés à CE PV
    $signatairesPV = $procesverbal->signataires;

    // Garder uniquement les membres de la commission
    // qui ont été sélectionnés pour CE PV
    $membresCommission = $signatairesPV->where(
        'type',
        'أعضاء لجنة الفرز'
    );
@endphp

<h5 dir="rtl">
    أعضاء لجنة الفرز
</h5>

<ul dir="rtl">
    @forelse($membresCommission as $membre)

        <li>
            {{ $membre->nom }} : {{ $membre->fonction }}
        </li>

    @empty

        <li>
            لا يوجد أعضاء محددون لهذا المحضر
        </li>

    @endforelse
</ul>


            <p dir="rtl">

                تم فتح العروض المقدمة من طرف العارضين والمبينة بالجدول التالي :

            </p>


            @php

                $fournisseurs = $consultation->offres->groupBy('fournisseur_id');

                $fournisseurRetenu = null;

                foreach($fournisseurs as $id => $offres){

                    $ok = true;

                    foreach($consultation->articles as $article){

                        $prix = [];

                        foreach($fournisseurs as $fid => $liste){

                            $o = $liste
                                ->where('article_id', $article->id)
                                ->first();

                            if(
                                $o &&
                                $o->prix_unitaire_ht !== null
                            ){

                                $prix[$fid] = $o->prix_unitaire_ht;

                            }

                        }

                        if(count($prix) == 0){

                            continue;

                        }

                        $minimum = min($prix);

                        if(
                            !isset($prix[$id]) ||
                            $prix[$id] != $minimum
                        ){

                            $ok = false;

                            break;

                        }

                    }

                    if($ok){

                        $fournisseurRetenu = $id;

                        break;

                    }

                }

            @endphp


            @php

                $hasDescription = $consultation->articles->contains(function ($article) {

                    return !empty(
                        trim($article->description ?? '')
                    );

                });

            @endphp


            <table
                class="table table-bordered text-center align-middle"
                dir="rtl"
            >

                <thead>

                    <tr style="background:#d9d9d9">

                        <th rowspan="2" style="width:5%">
                            ع/ر
                        </th>


                        <th rowspan="2" style="width:30%">

                            بيان الفصل

                        </th>


                        @if($hasDescription)

                            <th rowspan="2" style="width:20%">

                                الوصف

                            </th>

                        @endif


                        <th rowspan="2" style="width:8%">

                            الكمية

                        </th>


                        <th colspan="{{ $fournisseurs->count() }}">

                            عروض المزودين : السعر الفردي مع الأداءات

                        </th>


                        <th rowspan="2" style="width:12%">

                            السعر الفردي الأدنى مع الأداءات

                        </th>


                        <th rowspan="2" style="width:12%">

                            السعر الجملي مع الأداءات

                        </th>

                    </tr>


                    <tr style="background:#d9d9d9">

                        @foreach($fournisseurs as $id => $offres)

                            <th>

                                {{ $offres->first()->fournisseur->name }}

                            </th>

                        @endforeach

                    </tr>

                </thead>


                <tbody>

                @php

                    $numero = 1;

                    $grandTotal = 0;

                @endphp


                @foreach($consultation->articles as $article)

                    @php

                        /*
                         * Récupérer tous les prix numériques
                         * de cette ligne.
                         * ND est ignoré.
                         */

                        $prix = [];

                        foreach($fournisseurs as $id => $offres){

                            $offre = $offres
                                ->where('article_id', $article->id)
                                ->first();

                            if(
                                $offre &&
                                $offre->prix_unitaire_ht !== null
                            ){

                                $prix[] = (float) $offre->prix_unitaire_ht;

                            }

                        }


                        /*
                         * Trouver le prix le plus bas
                         */

                        $minimum = count($prix)
                            ? min($prix)
                            : null;


                        /*
                         * Calcul du total
                         */

                        $total = $minimum !== null
                            ? $minimum * $article->quantite
                            : null;


                        if($total !== null){

                            $grandTotal += $total;

                        }

                    @endphp


                    <tr>

                        <td>

                            {{ $numero++ }}

                        </td>


                        <td class="text-end">

                            {{ $article->designation }}

                        </td>


                        @if($hasDescription)

                            <td class="text-end">

                                {{ $article->description ?? '' }}

                            </td>

                        @endif


                        <td>

                            {{ $article->quantite }}

                        </td>


                        @foreach($fournisseurs as $id => $offres)

                            @php

                                $offre = $offres
                                    ->where('article_id', $article->id)
                                    ->first();


                                /*
                                 * Vérifier si ce prix
                                 * est le minimum de la ligne
                                 */

                                $estMinimum = false;

                                if(
                                    $offre &&
                                    $offre->prix_unitaire_ht !== null &&
                                    $minimum !== null &&
                                    (float) $offre->prix_unitaire_ht == $minimum
                                ){

                                    $estMinimum = true;

                                }

                            @endphp


                            <td
                                @if($estMinimum)
                                    style="background:#d9ead3; font-weight:bold;"
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


                        <td style="font-weight:bold">

                            @if($minimum !== null)

                                {{ number_format(
                                    $minimum,
                                    3
                                ) }}

                            @else

                                ND

                            @endif

                        </td>


                        <td style="font-weight:bold">

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


                <tr style="background:#d9d9d9;font-weight:bold">

                    <td colspan="{{ 4 + $fournisseurs->count() + ($hasDescription ? 1 : 0) }}">

                        المجموع

                    </td>


                    <td>

                        {{ number_format(
                            $grandTotal,
                            3
                        ) }}

                    </td>

                </tr>

                </tbody>

            </table>


            <hr>


            <h5 class="mb-3" dir="rtl">

                الملاحظات

            </h5>


            <form
                action="{{ route(
                    'procesverbal.update',
                    $procesverbal->id
                ) }}"
                method="POST"
                dir="rtl"
            >

                @csrf

                @method('PUT')


                <textarea
                    name="commentaire"
                    class="form-control"
                    rows="6"
                    placeholder="اكتب الملاحظات هنا..."
                >{{ $procesverbal->commentaire }}</textarea>


                <div class="mt-3">

                    <button class="btn btn-primary">

                        حفظ الملاحظات

                    </button>

                </div>

            </form>


            <hr>


@php
    $signataires = $procesverbal->signataires;

    $membresCommission = $signataires->filter(function ($signataire) {
        return $signataire->type === 'أعضاء لجنة الفرز';
    });

    $doyen = $signataires->first(function ($signataire) {
        return $signataire->type === 'العميد';
    });
@endphp

<div class="row mt-5" dir="rtl">

    <div class="col-md-6">

        <h5>
            الإمضاء
        </h5>

        @forelse($membresCommission as $membre)

            <p>
                {{ $membre->nom }} :
                ............................................
            </p>

        @empty

            <p>
                ............................................
            </p>

        @endforelse

    </div>


    {{-- العميد --}}
    <div class="col-md-6 text-end">

        <p>
            قابس في :
            {{ date('d/m/Y') }}
        </p>

        <br>
        <br>

        <h5>
            العميــــد
        </h5>

        <strong>
            {{ $doyen?->nom ?? '........................' }}
        </strong>

    </div>

</div>


                <div class="mt-3">

                    <a
                        href="{{ route('procesverbal.index') }}"
                        class="btn btn-secondary"
                    >

                        Retour

                    </a>


                    <a
                        href="{{ route(
                            'procesverbal.pdf.form',
                            $procesverbal->id
                        ) }}"
                        class="btn btn-danger"
                    >

                        Générer PDF

                    </a>


                    <a
                        href="{{ route(
                            'procesverbal.excel',
                            $consultation->id
                        ) }}"
                        class="btn btn-success"
                    >

                        Exporter Excel

                    </a>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection
