# Import Excel intelligent FSG Achat

La page **Import Excel** accepte les fichiers `.xls` et `.xlsx` réels de l’application, sans obliger l’utilisateur à utiliser un modèle.

## Types d’import

L’utilisateur choisit `Consultation`, `Procès-verbal` ou `Répartition`, saisit le service demandeur et sélectionne son fichier.

Le parseur détecte notamment les libellés arabes suivants : `إستشارة عدد`, `الموضوع`, `بيان المطلوب`, `بيان الفصل`, `بيان المشتريات`, `الكمية` et les noms de fournisseurs dans les en-têtes.

La référence `إستشارة عدد 07/ 2026` devient `07/2026`. Les identifiants techniques ne sont jamais demandés à l’utilisateur.

## Persistance

Après confirmation, une transaction sauvegarde définitivement la consultation, la demande associée, les articles, les fournisseurs et les offres. Un procès-verbal est créé automatiquement s’il n’existe pas. La répartition est visible automatiquement à partir des offres ; le projet n’a pas de table de répartition séparée.

Le service demandeur est enregistré dans `demande.service_demandeur` et affiché dans la liste des consultations.

## Prérequis

Pour lire certains fichiers `.xls` contenant des objets/images, PHP doit disposer de l’extension GD en plus des extensions habituelles : `mbstring`, `xml`, `zip`, `sqlite3`.

```bash
composer install
php artisan key:generate
php artisan migrate --seed
php artisan optimize:clear
php artisan serve
```

Sous Windows, il faut aussi activer `extension=gd` dans le `php.ini` utilisé par le terminal.

## Test avec le fichier fourni

Le fichier `استشارة072026مطبوعاتخصوصية.xls` a été vérifié :

- référence : `07/2026` ;
- objet : `شراء مطبوعات خصوصية (اوراق الامتحانات)` ;
- articles : 4 ;
- fournisseurs : 3 ;
- erreurs de détection : 0.

Les exports Excel/PDF existants ne sont pas réécrits.
