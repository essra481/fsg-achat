@php
    $hasDescription = $consultation->articles->contains(function ($article) {
        return trim($article->description ?? '') !== '';
    });
@endphp

<table dir="rtl" style="border-collapse: collapse; width: 100%;">

    {{-- ================= EN-TÊTE ================= --}}

    <tr>
        <td colspan="6"
            style="border: none; text-align: right; font-size: 16px; font-weight: bold;">
            الجمهورية التونسية
        </td>
    </tr>

    <tr>
        <td colspan="6"
            style="border: none; text-align: right; font-size: 16px; font-weight: bold;">
            وزارة التعليم العالي والبحث العلمي
        </td>
    </tr>

    <tr>
        <td colspan="6"
            style="border: none; text-align: right; font-size: 16px; font-weight: bold;">
            جامعة قابس
        </td>
    </tr>

    <tr>
        <td colspan="6"
            style="border: none; text-align: right; font-size: 16px; font-weight: bold;">
            كلية العلوم بقابس
        </td>
    </tr>

    <tr>
        <td colspan="6" style="border: none; height: 20px;"></td>
    </tr>


    {{-- ================= TITRE ================= --}}

    <tr>
        <td colspan="6"
            style="border: none; text-align: center; font-size: 24px; font-weight: bold;">

            إستشارة عدد {{ $consultation->reference }}

        </td>
    </tr>

    <tr>
        <td colspan="6" style="border: none; height: 20px;"></td>
    </tr>


    {{-- ================= TEXTE ================= --}}

    <tr>
        <td colspan="6"
            style="border: none; text-align: right; font-size: 15px;">

            تعتزم كلية العلوم بقابس شراء

            <strong>{{ $consultation->objet }}</strong>

            بعنوان السنة المالية

            <strong>{{ date('Y') }}</strong>

            فالرجاء مدنا بالأسعار حسب الجدول التالي في أجل أقصاه يوم

            <strong>
                {{ \Carbon\Carbon::parse($consultation->date_limite)->format('d/m/Y') }}
            </strong>

        </td>
    </tr>

    <tr>
        <td colspan="6"
            style="border: none; text-align: right; font-size: 15px;">

            تودع العروض مباشرة بمكتب الضبط بالكلية المركب الجامعي حي الرياض زريق قابس
            أو ترسل عن طريق البريد إلى العنوان التالي :

        </td>
    </tr>

    <tr>
        <td colspan="6"
            style="border: none; text-align: right; font-size: 15px;">

            كلية العلوم بقابس

        </td>
    </tr>

    <tr>
        <td colspan="6"
            style="border: none; text-align: right; font-size: 15px;">

            حي الرياض 6072 زريق قابس

        </td>
    </tr>

    <tr>
        <td colspan="6"
            style="border: none; text-align: right; font-size: 15px;">

            في ظرف مغلق لا يحمل أي عبارة تدل على هوية العارض ومكتوب عليه :

        </td>
    </tr>

    <tr>
        <td colspan="6"
            style="border: none; text-align: center; font-size: 15px; font-weight: bold;">

            لا يفتح إستشارة عدد
            {{ $consultation->reference }}
            شراء
            {{ $consultation->objet }}

        </td>
    </tr>

    <tr>
        <td colspan="6"
            style="border: none; text-align: right; font-size: 15px;">

            علما أنه يتم اعتماد ختم مكتب الضبط لتحديد تاريخ وصول العروض.

        </td>
    </tr>

    <tr>
        <td colspan="6" style="border: none; height: 25px;"></td>
    </tr>


    {{-- ================= TABLEAU ARTICLES ================= --}}

    <tr>

        <th style="border:1px solid black; padding:8px;">
            ع/ر
        </th>

        <th style="border:1px solid black; padding:8px;">
            بيان المطلوب
        </th>

        @if($hasDescription)
            <th style="border:1px solid black; padding:8px;">
                Description
            </th>
        @endif

        <th style="border:1px solid black; padding:8px;">
            الكمية
        </th>

        <th style="border:1px solid black; padding:8px;">
            السعر الفردي باعتبار كل الأداء
        </th>

        <th style="border:1px solid black; padding:8px;">
            السعر الجملي باعتبار كل الأداء
        </th>

    </tr>


    {{-- ================= ARTICLES ================= --}}

    @forelse($consultation->articles as $article)

        <tr>

            <td style="border:1px solid black; padding:8px;">
                {{ $loop->iteration }}
            </td>

            <td style="border:1px solid black; padding:8px;">
                {{ $article->designation }}
            </td>

            @if($hasDescription)
                <td style="border:1px solid black; padding:8px;">
                    {{ $article->description ?? '' }}
                </td>
            @endif

            <td style="border:1px solid black; padding:8px;">
                {{ $article->quantite ?? 0 }}
            </td>

            <td style="border:1px solid black; padding:8px;">
                {{ $article->prix_unitaire ?? '' }}
            </td>

            <td style="border:1px solid black; padding:8px;">
                {{ $article->prix_total ?? '' }}
            </td>

        </tr>

    @empty

        <tr>
            <td colspan="{{ $hasDescription ? 6 : 5 }}"
                style="border:1px solid black; padding:8px; text-align:center;">
                لا توجد مقالات
            </td>
        </tr>

    @endforelse


    {{-- ================= TOTAL ================= --}}

    <tr>

        <td colspan="{{ $hasDescription ? 5 : 4 }}"
            style="
                border:1px solid black;
                padding:8px;
                background-color:#d9d9d9;
                font-weight:bold;
                text-align:right;
            ">

            المجموع باعتبار كل الأداء

        </td>

        <td style="
            border:1px solid black;
            padding:8px;
            background-color:#d9d9d9;
            font-weight:bold;
            text-align:center;
        ">
        </td>

    </tr>


    {{-- ================= ESPACE ================= --}}

    <tr>
        <td colspan="6"
            style="border: none; height: 30px;">
        </td>
    </tr>


   {{-- ================= FOURNISSEUR + SIGNATURE ================= --}}

{{-- ================= FOURNISSEUR + SIGNATURE ================= --}}

<tr>

    {{-- FOURNISSEUR --}}
    <td colspan="4"
        style="
            border: none;
            text-align: right;
            font-size: 15px;
            font-weight: bold;
            vertical-align: top;
        ">

        المعرف الجبائي :
        ............................................................

        <br><br>

        إمضاء وختم المزود

    </td>

    {{-- قابس في --}}
    <td
        style="
            border: none;
            text-align: left;
            font-size: 15px;
            font-weight: bold;
            vertical-align: top;
        ">

        قابس في

    </td>

    {{-- DATE --}}
    <td
        style="
            border: none;
            text-align: left;
            font-size: 15px;
            font-weight: bold;
            vertical-align: top;
        ">

        {{ \Carbon\Carbon::parse($consultation->date_lancement)->format('d/m/Y') }}

    </td>

</tr>

<tr>

    {{-- FOURNISSEUR --}}
    <td colspan="4"
        style="border: none;">
    </td>

    {{-- العميد + nom dans UNE SEULE cellule --}}
    <td colspan="2"
        style="
            border: none;
        text-align: left;
        font-size: 15px;
        font-weight: bold;
        vertical-align: top;
        width: 220px;
        min-width: 220px;
        height: 80px;
        padding: 10px 15px;
        white-space: nowrap;
        ">

        العميد

        <br><br>

        {{ $doyen ? $doyen->nom : 'DOYEN NON TROUVÉ' }}

    </td>

</tr>

</table>