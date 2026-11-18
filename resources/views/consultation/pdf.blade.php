<!DOCTYPE html>
<html lang="ar">

<head>

<meta charset="UTF-8">

<style>

@page{
    size:A4 portrait;
    margin:15mm;
}

body{
    font-family: DejaVu Sans, sans-serif;
    font-size:15px;
    direction:rtl;
    line-height:1.8;
}

h2{
    text-align:center;
    margin-bottom:30px;
}

p{
    text-align:justify;
}

table{
    width:100%;
    border-collapse:collapse;
    margin-top:20px;
}

table,
th,
td{
    border:1px solid black;
}

th,
td{
    padding:8px;
    text-align:center;
    vertical-align:middle;
}

.footer{
    margin-top:40px;
    width:100%;
}

.left{
    float:left;
    text-align:left;
}

.right{
    float:right;
    text-align:right;
}

.clear{
    clear:both;
}

</style>

</head>

<body>
    
<div style="width:100%; overflow:hidden; margin-bottom:30px;">

    <!-- Logo -->
    <div style="float:left; width:20%;">
        @php
            $image = base64_encode(file_get_contents(public_path('images/f.jpg')));
        @endphp

        <img src="data:image/jpeg;base64,{{ $image }}" style="width:100px;">
    </div>

    <!-- Texte -->
    <div style="float:right; width:75%; text-align:right; direction:rtl;">

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
<div style="clear:both;"></div>

<div style="width:100%; text-align:center;">
    <span style="font-size:24px; font-weight:bold;">
        إستشارة عدد {{ $consultation->reference }}
    </span>
</div>

<div>
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

    <p style="text-align:center;font-weight:bold;">

    لا يفتح إستشارة عدد

    {{ $consultation->reference }}

    شراء

    {{ $consultation->objet }}

    </p>

    <p>

    علما أنه يتم اعتماد ختم مكتب الضبط لتحديد تاريخ وصول العروض.

    </p>
</div>
@php
    $hasDescription = $consultation->articles->contains(function ($article) {
        return trim($article->description ?? '') !== '';
    });
@endphp
<table>

<thead>

<tr>

    <th style="width:8%">ع/ر</th>

    <th>بيان المطلوب</th>

    @if($hasDescription)
        <th>الوصف</th>
    @endif

    <th style="width:12%">الكمية</th>

    <th style="width:18%">
        السعر الفردي باعتبار كل الآداء
    </th>

    <th style="width:18%">
        السعر الجملي باعتبار كل الآداء
    </th>

</tr>

</thead>

<tbody>

@forelse($consultation->articles as $article)

<tr>

    <td>
        {{ $loop->iteration }}
    </td>

    <td>
        {{ $article->designation }}
    </td>

    @if($hasDescription)
        <td>
            {{ $article->description ?? '' }}
        </td>
    @endif

    <td>
        {{ $article->quantite ?? 0 }}
    </td>

    <td></td>

    <td></td>

</tr>

@empty

<tr>

    <td colspan="{{ $hasDescription ? 6 : 5 }}">
        لا توجد مقالات
    </td>

</tr>

@endforelse

<tr>

    <th colspan="{{ $hasDescription ? 5 : 4 }}"
        style="text-align:right;">

        المجموع باعتبار كل الأداء

    </th>

    <th>&nbsp;</th>

</tr>

</tbody>

</table>

<br><br>


<div style="width:100%; margin-top:30px; overflow:hidden;">

    {{-- GAUCHE : date + doyen --}}
    <div style="float:left; width:45%; text-align:left;">

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
    <div style="float:right; width:45%; text-align:right;">

        <strong>

            المعرف الجبائي :

            <br>

            .....................................................

            <br><br>

            إمضاء وختم المزود

        </strong>

    </div>

</div>

<div style="clear:both;"></div>

<div class="clear"></div>


</body>

</html>