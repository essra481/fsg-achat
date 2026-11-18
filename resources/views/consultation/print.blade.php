<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>

    <meta charset="UTF-8">

    <title>Consultation {{ $consultation->reference }}</title>

    <style>

        body{
            font-family: DejaVu Sans, sans-serif;
            margin:40px;
            direction:rtl;
            font-size:18px;
            line-height:35px;
            
        }

        h2{
            text-align:center;
        }

        table{
            width:100%;
            border-collapse:collapse;
            margin-top:25px;
        }

        table th,
        table td{
            border:1px solid black;
            padding:8px;
            text-align:center;
        }

        .signature{
            margin-top:70px;
            width:100%;
        }

        .left{
            float:left;
            text-align:center;
        }

        .right{
            float:right;
            text-align:center;
        }
       

    </style>

</head>

<body onload="window.print()">

<h2>

إستشارة عدد {{ $consultation->reference }}

</h2>

<p>

تعتزم كلية العلوم بقابس شراء

<strong>{{ $consultation->objet }}</strong>

بعنوان السنة المالية

{{ date('Y') }}

فالرجاء مدنا بالأسعار حسب الجدول التالي في أجل أقصاه يوم

<strong>

{{ \Carbon\Carbon::parse($consultation->date_limite)->format('d/m/Y') }}

</strong>

</p>

<p>

تودع العروض مباشرة بمكتب الضبط بالكلية المركب الجامعي حي الرياض زريق قابس

أو ترسل عن طريق البريد إلى العنوان التالي :

<br>

كلية العلوم بقابس

<br>

حي الرياض 6072 زريق قابس

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

<table>

<thead>

<tr>

<th>ع/ر</th>

<th>بيان المطلوب</th>

<th>الكمية</th>

<th>السعر الفردي</th>

<th>السعر الجملي</th>

</tr>

</thead>

<tbody>

@foreach($consultation->articles as $article)

<tr>

<td>{{ $loop->iteration }}</td>

<td>{{ $article->designation }}</td>

<td>{{ $article->quantite }}</td>

<td></td>

<td></td>

</tr>

@endforeach

</tbody>

</table>

<br><br>

<p>

المجموع باعتبار كل الأداء :

.....................................................

</p>

<p>

المعرف الجبائي :

.....................................................

</p>

<div class="signature">

<div class="right">

قابس في

{{ \Carbon\Carbon::parse($consultation->date_lancement)->format('d/m/Y') }}

<br><br>

العميد

<br>

عادل الصدي

</div>

<div class="left">

إمضاء وختم المزود

</div>

</div>

</body>

</html>