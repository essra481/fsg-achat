@csrf
<div class="mb-3">
    <label class="form-label">Name</label>
    <input type="text" name="name" class="form-control" required value="{{ old('raison_sociale', $fournisseur->raison_sociale ?? '') }}">
</div>
<div class="mb-3">
    <label class="form-label">Matricule fiscal</label>
    <input type="text" name="matricule_fiscal" class="form-control"  value="{{ old('matricule_fiscal', $fournisseur->matricule_fiscal ?? '') }}">
</div>
<div class="mb-3">
    <label class="form-label">Adresse</label>
    <input type="text" name="adresse" class="form-control" value="{{ old('adresse', $fournisseur->adresse ?? '') }}">
</div>
<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Telephone</label>
        <input type="text" name="telephone" class="form-control" value="{{ old('telephone', $fournisseur->telephone ?? '') }}">
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="{{ old('email', $fournisseur->email ?? '') }}">
    </div>
</div>
<div class="mb-3">
    <label class="form-label">Nom du contact</label>
    <input type="text" name="contact_nom" class="form-control" value="{{ old('contact_nom', $fournisseur->contact_nom ?? '') }}">
</div>
<div class="mb-3">
    <label class="form-label">Observations</label>
    <textarea name="observations" class="form-control" rows="3">{{ old('observations', $fournisseur->observations ?? '') }}</textarea>
</div>

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<button type="submit" class="btn btn-primary">Enregistrer</button>
<a href="{{ route('fournisseurs.index') }}" class="btn btn-link">Annuler</a>
