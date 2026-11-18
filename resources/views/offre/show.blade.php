```php
@extends('layouts.app') 
 
@section('content') 
 
<div class="container"> 
 
    <div class="card shadow"> 
 
        <div class="card-header bg-primary text-white"> 
 
            <h4 class="mb-0"> 
                Comparaison des offres 
            </h4> 
 
        </div> 
 
        <div class="card-body"> 
 
            <h5> 
                استشارة عدد : 
                {{ $consultation->reference }} 
            </h5> 
 
            <p> 
                <strong> 
                    الموضوع : 
                </strong> 
 
                {{ $consultation->objet }} 
            </p> 
 
            <hr> 
 
            @if($consultation->offres->count() > 0) 
 
                <div class="table-responsive"> 
 
                    <table class="table table-bordered text-center align-middle"> 
 
                        <thead class="table-dark"> 
 
                            <tr> 
 
                                <th rowspan="2"> 
                                    ع/ر 
                                </th> 
 
                                <th rowspan="2"> 
                                    بيان الفصل 
                                </th>

                                <th rowspan="2"> 
                                    الوصف 
                                </th>
 
                                <th rowspan="2"> 
                                    الكمية 
                                </th> 
 
                                @foreach($consultation->fournisseurs as $fournisseur) 
 
                                    <th> 
                                        {{ $fournisseur->name }} 
                                    </th> 
 
                                @endforeach 
 
                                <th rowspan="2"> 
                                    السعر الفردي الأدنى 
                                </th> 
 
                                <th rowspan="2"> 
                                    السعر الجملي 
                                </th> 
 
                            </tr> 
 
                        </thead> 
 
 
                        <tbody> 
 
                        @php 
 
                            $numero = 1; 
                            $grandTotal = 0; 
 
                        @endphp 
 
 
                        @foreach($consultation->articles as $article) 
 
                            @php 
 
                                $prix = []; 
 
                                /*
                                 * Récupérer tous les prix numériques
                                 * pour trouver le minimum.
                                 * Les offres ND sont ignorées.
                                 */
                                foreach($consultation->fournisseurs as $fournisseur) {

                                    $offre = $consultation->offres
                                        ->where('article_id', $article->id)
                                        ->where('fournisseur_id', $fournisseur->id)
                                        ->first();

                                    if(
                                        $offre &&
                                        $offre->prix_unitaire_ht !== null
                                    ) {

                                        $prix[] = (float) $offre->prix_unitaire_ht;

                                    }

                                }

                                if(count($prix) > 0) {

                                    $minimum = min($prix);

                                } else {

                                    $minimum = null;

                                }

                            @endphp 
 
 
                            <tr> 
 
                                <td> 
                                    {{ $numero++ }} 
                                </td> 
 
 
                                <td> 
                                    {{ $article->designation }} 
                                </td>

 
                                <td> 
                                    {{ $article->description ?? '' }} 
                                </td>
 
 
                                <td> 
                                    {{ $article->quantite }} 
                                </td> 
 
 
                                @foreach($consultation->fournisseurs as $fournisseur) 
 
                                    @php 
 
                                        $offre = 
                                            $consultation->offres 
                                            ->where( 
                                                'article_id', 
                                                $article->id 
                                            ) 
                                            ->where( 
                                                'fournisseur_id', 
                                                $fournisseur->id 
                                            ) 
                                            ->first(); 

                                        $estMinimum = false;

                                        if(
                                            $offre &&
                                            $offre->prix_unitaire_ht !== null &&
                                            $minimum !== null &&
                                            (float) $offre->prix_unitaire_ht == $minimum
                                        ) {

                                            $estMinimum = true;

                                        }
 
                                    @endphp 
 
 
                                    <td
                                        @if($estMinimum)
                                            class="prix-minimum"
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
 
                                        @elseif($offre) 
 
                                            <strong>ND</strong>
 
                                        @else 
 
                                            - 
 
                                        @endif 
 
                                    </td> 
 
                                @endforeach 
 
 
                                @php 
 
                                    if($minimum !== null) {

                                        $total = 
                                            $minimum * 
                                            $article->quantite;

                                        $grandTotal += $total;

                                    } else {

                                        $total = null;

                                    }
 
                                @endphp 
 
 
                                <td> 
 
                                    @if($minimum !== null) 
 
                                        {{ number_format( 
                                            $minimum, 
                                            3 
                                        ) }} 
 
                                    @else 
 
                                        - 
 
                                    @endif 
 
                                </td> 
 
 
                                <td> 
 
                                    @if($total !== null) 
 
                                        {{ number_format( 
                                            $total, 
                                            3 
                                        ) }} 
 
                                    @else 
 
                                        - 
 
                                    @endif 
 
                                </td> 
 
 
                            </tr> 
 
 
                        @endforeach 
 
 
                        <tr> 
 
                            <th 
                                colspan="{{ 4 + $consultation->fournisseurs->count() }}"> 
 
                                المجموع 
 
                            </th> 
 
 
                            <th colspan="2"> 
 
                                {{ number_format( 
                                    $grandTotal, 
                                    3 
                                ) }} 
 
                            </th> 
 
                        </tr> 
 
 
                        </tbody> 
 
                    </table> 
 
                </div> 
 
 
            @else 
 
                <div class="alert alert-warning text-center"> 
 
                    Aucune offre n'a encore été enregistrée. 
 
                </div> 
 
            @endif 
 
 
            <div class="mt-4"> 
 
                <a 
                    href="{{ route( 
                        'offre.edit', 
                        $consultation->id 
                    ) }}" 
                    class="btn btn-secondary"> 
 
                    Retour 
 
                </a> 
 
            </div> 
 
        </div> 
 
    </div> 
 
</div>


<style>

/* Prix minimum */

.prix-minimum {
    background-color: #d1e7dd !important;
    color: #0f5132;
    font-weight: bold;
}

</style>
 
@endsection

