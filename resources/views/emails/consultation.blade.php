<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
</head>
<body>

<p>Madame, Monsieur,</p>

<p>
Veuillez trouver en pièce jointe notre consultation
<strong>{{ $consultation->reference }}</strong>.
</p>

<p>
Nous vous remercions de bien vouloir nous transmettre votre offre
avant le <strong>{{ $consultation->date_limite }}</strong>.
</p>

<p>Cordialement.</p>

</body>
</html>