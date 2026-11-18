<style>
    /* Supprimer les flèches des champs quantité */
    input[type="number"].no-spinner::-webkit-inner-spin-button,
    input[type="number"].no-spinner::-webkit-outer-spin-button {
        -webkit-appearance: none;
        appearance: none;
        margin: 0;
    }

    input[type="number"].no-spinner {
        -moz-appearance: textfield;
        appearance: textfield;
    }
</style>
<div class="container">

    <div class="card shadow p-5">
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

        <div class="text-center mb-5">
            <h2 class="fw-bold">
                إستشارة عدد {{ $consultation->reference }}
            </h2>
        </div>

        <div dir="rtl" style="font-size:18px;line-height:40px;">

            <p>

                تعتزم كلية العلوم بقابس شراء

                <strong>{{ $consultation->objet }}</strong>

                بعنوان السنة المالية

                {{ date('Y') }}

                فالرجاء مدنا بالأسعار حسب الجدول التالي في أجل أقصاه يوم

                <strong>
                    {{ \Carbon\Carbon::parse($consultation->date_limite)->format('d/m/Y') }}
                </strong>

                تودع العروض مباشرة بمكتب الضبط بالكلية المركب الجامعي حي الرياض زريق قابس
                أو ترسل عن طريق البريد إلى العنوان التالي :

                كلية العلوم بقابس
                حي الرياض 6072 زريق قابس

                في ظرف مغلق لا يحمل أي عبارة تدل على هوية العارض ومكتوب عليه :

            </p>

            <div class="text-center fw-bold my-3">

                لا يفتح إستشارة عدد

                {{ $consultation->reference }}

                شراء

                {{ $consultation->objet }}

            </div>

            <p>

                علما أنه يتم اعتماد ختم مكتب الضبط لتحديد تاريخ وصول العروض.

            </p>

        </div>

        <hr>

        <form action="{{ route('consultation.saveArticles',$consultation->id) }}" method="POST">

            @csrf

            <h5 class="mb-3">
                Articles demandés
            </h5>

            <div class="d-flex gap-2 mb-3">

    </div>


<table class="table table-bordered text-center align-middle" id="articlesTable">

    <thead class="table-light">
        <tr>
            <th style="width:70px;">N°</th>

            <th>Désignation</th>

            {{-- Description cachée au début --}}
            <th class="description-column" style="display:none;">
                Description
            </th>

            <th style="width:120px;">Quantité</th>

            <th style="width:150px;">
                Prix unitaire HT
            </th>

            <th style="width:150px;">
                Prix total HT
            </th>

            <th style="width:120px;">Action</th>
        </tr>
    </thead>

    <tbody>

        @forelse($consultation->articles as $index => $article)

            <tr>

                <td class="numero">
                    {{ $index + 1 }}
                </td>

                <td>
                    <input
                        type="text"
                        name="designation[]"
                        class="form-control"
                        value="{{ $article->designation }}"
                    >
                </td>

                {{-- Description --}}
                <td class="description-column" style="display:none;">
                    <textarea
                        name="description[]"
                        class="form-control"
                        rows="1"
                    >{{ $article->description }}</textarea>
                </td>

                <td>
                    <input 
                    type="number" 
                    name="quantite[]" 
                    class="form-control no-spinner" 
                    value="{{ $article->quantite }}" 
                    min="0"
                    step="1"
>
                </td>

                <td>
                    <input 
                        type="text" 
                        name="prix_unitaire[]" 
                        class="form-control no-cursor" 
                        value="{{ $article->prix_unitaire }}"
                        readonly
                    >
                </td>

                <td>
                    <input 
                        type="text" 
                        name="prix_total[]" 
                        class="form-control no-cursor" 
                        value="{{ $article->prix_total }}"
                        readonly
                    >
                </td>

                <td>
                    <button type="button"
                            class="btn btn-danger removeRow">
                        Supprimer
                    </button>
                </td>

            </tr>

        @empty

            <tr>

                <td class="numero">1</td>

                <td>
                    <input
                        type="text"
                        name="designation[]"
                        class="form-control"
                        placeholder="Désignation"
                    >
                </td>

                {{-- Description cachée --}}
                <td class="description-column" style="display:none;">
                    <textarea
                        name="description[]"
                        class="form-control"
                        rows="1"
                        placeholder="Description"
                    ></textarea>
                </td>

                <td>
                    <input
                        type="number"
                        name="quantite[]"
                        class="form-control"
                        placeholder="Qté"
                    >
                </td>

                <td>
                    <input
                        type="text"
                        name="prix_unitaire[]"
                        class="form-control"
                    >
                </td>

                <td>
                    <input
                        type="text"
                        name="prix_total[]"
                        class="form-control"
                    >
                </td>

                <td>
                    <button type="button"
                            class="btn btn-danger removeRow">
                        Supprimer
                    </button>
                </td>

            </tr>

        @endforelse

    </tbody>

    <tfoot>

        @if($consultation->articles->count() > 0)

            <tr>

                <th colspan="5" style="text-align:right;">
                    المجموع باعتبار كل الأداء
                </th>

                <th>
                    <input
                        type="text"
                        class="form-control"
                        name="montant_total"
                        readonly
                    >
                </th>

                <th></th>

            </tr>

        @endif

    </tfoot>

</table>

            <button
                type="submit"
                class="btn btn-primary ms-2">

                Enregistrer la consultation

            </button>
            <button type="button" class="btn btn-success" id="addRow">
                + Ajouter une ligne
            </button>

            <button type="button" class="btn btn-secondary" id="toggleDescription">
                + Ajouter Description
            </button>

            <hr>
        <div dir="rtl">
            <h5>

                المعرف الجبائي :

                ................................................

            </h5>

        </div>

        <br><br>

            <div class="row mt-4">

    {{-- GAUCHE : date + doyen --}}
    <div class="col-6 text-start">

        <strong>

            قابس في

            {{ \Carbon\Carbon::parse($consultation->date_lancement)->format('d/m/Y') }}

            <br><br>

            العميد

            <br>

            {{ $doyen?->nom ?? '........................' }}

        </strong>

    </div>


    {{-- DROITE : fournisseur --}}
    <div class="col-6 text-end">

        <strong>
            إمضاء وختم المزود
        </strong>

    </div>

</div>

            

        </div>

        </form>

    </div>

</div>

<script>

    // ==============================
    // ÉTAT DE LA DESCRIPTION
    // ==============================

    let descriptionVisible = false;


    // ==============================
    // AJOUTER UNE LIGNE
    // ==============================

    document.getElementById('addRow').addEventListener('click', function () {

        let table = document.querySelector('#articlesTable tbody');

        let rowCount = table.rows.length + 1;

        let row = `
            <tr>

                <td class="numero">
                    ${rowCount}
                </td>

                <td>
                    <input
                        type="text"
                        name="designation[]"
                        class="form-control"
                        placeholder="Désignation"
                    >
                </td>

                <td class="description-column"
                    style="display:${descriptionVisible ? 'table-cell' : 'none'};">

                    <textarea
                        name="description[]"
                        class="form-control"
                        rows="1"
                        placeholder="Description"
                    ></textarea>

                </td>

                <td>
                    <input
                        type="number"
                        name="quantite[]"
                        class="form-control"
                        placeholder="Qté"
                    >
                </td>

                <td>
                    <input
                        type="text"
                        name="prix_unitaire[]"
                        class="form-control"
                    >
                </td>

                <td>
                    <input
                        type="text"
                        name="prix_total[]"
                        class="form-control"
                    >
                </td>

                <td>
                    <button
                        type="button"
                        class="btn btn-danger removeRow">
                        Supprimer
                    </button>
                </td>

            </tr>
        `;

        table.insertAdjacentHTML('beforeend', row);

    });


    // ==============================
    // AFFICHER / CACHER DESCRIPTION
    // ==============================

    document.getElementById('toggleDescription').addEventListener('click', function () {

        descriptionVisible = !descriptionVisible;

        let columns = document.querySelectorAll('.description-column');

        columns.forEach(function(column) {

            column.style.display =
                descriptionVisible ? 'table-cell' : 'none';

        });


        if (descriptionVisible) {

            this.innerHTML = '− Masquer Description';

            this.classList.remove('btn-secondary');
            this.classList.add('btn-warning');

        } else {

            this.innerHTML = '+ Ajouter Description';

            this.classList.remove('btn-warning');
            this.classList.add('btn-secondary');

        }

    });


    // ==============================
    // SUPPRIMER UNE LIGNE
    // ==============================

    document.addEventListener('click', function(e) {

        if (e.target.classList.contains('removeRow')) {

            let rows = document.querySelectorAll(
                '#articlesTable tbody tr'
            );

            if (rows.length == 1) {

                alert(
                    'La consultation doit contenir au moins un article.'
                );

                return;
            }

            e.target.closest('tr').remove();


            // Renumérotation

            let lignes = document.querySelectorAll(
                '#articlesTable tbody tr'
            );

            lignes.forEach(function(row, index) {

                row.querySelector('.numero').innerHTML = index + 1;

            });

        }

    });

</script>