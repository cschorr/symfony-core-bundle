# Address Fields in EasyAdmin

## Overview

This documentation explains how to add address fields to your EasyAdmin CRUD controllers for entities that use the `SetAddressTrait`.

## Implementation

### CompanyCrudController Example

```php
use EasyCorp\Bundle\EasyAdminBundle\Field\CountryField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

public function configureFields(string $pageName): iterable
{
    $fields = [
        IdField::new('id')->hideOnForm()->hideOnIndex(),
        TextField::new('name'),
        TextField::new('nameExtension')->setLabel($this->translator->trans('Description')),
        TextField::new('url')->setLabel($this->translator->trans('Website')),
    ];

    // Add address fields
    if ($pageName !== Crud::PAGE_INDEX) {
        // Add address panel for forms
        $fields[] = FormField::addPanel($this->translator->trans('Address Information'))
            ->setIcon('fas fa-map-marker-alt')
            ->collapsible();
        
        $fields[] = TextField::new('street')
            ->setLabel($this->translator->trans('Street Address'))
            ->setRequired(false);
        
        $fields[] = TextField::new('zip')
            ->setLabel($this->translator->trans('ZIP/Postal Code'))
            ->setRequired(false);
            
        $fields[] = TextField::new('city')
            ->setLabel($this->translator->trans('City'))
            ->setRequired(false);
            
        $fields[] = CountryField::new('countryCode')
            ->setLabel($this->translator->trans('Country'))
            ->setRequired(false);
    } else {
        // On index page, show address summary
        $fields[] = TextField::new('city')
            ->setLabel($this->translator->trans('City'));
        $fields[] = CountryField::new('countryCode')
            ->setLabel($this->translator->trans('Country'));
    }

    // ... rest of your fields

    return $fields;
}
```

## Entity Requirements

Your entity must use the `SetAddressTrait` to have the required address fields:

```php
use App\Entity\Traits\Set\SetAddressTrait;

class Company extends AbstractEntity
{
    use SetAddressTrait;
    // ... other code
}
```

## Address Fields

The `SetAddressTrait` provides these fields:
- `street` (string, nullable)
- `zip` (string, nullable)
- `city` (string, nullable)
- `countryCode` (string, nullable) - ISO 3166-1 alpha-2 format

## EasyAdmin Fields Used

- **TextField**: For street, zip, and city
- **CountryField**: For country selection with built-in country dropdown
- **FormField::addPanel()**: For organizing fields in a collapsible section

## Benefits of This Approach

1. **Pure EasyAdmin**: Uses only built-in EasyAdmin fields
2. **CountryField**: Automatic country dropdown with proper ISO codes
3. **No Custom Templates**: Everything handled by EasyAdmin's rendering
4. **Collapsible Panel**: Better UX with organized address section
5. **Index Summary**: Shows relevant address info on list pages

## Translation Keys

Add these keys to your translation files (e.g., `translations/messages.en.yaml`):

```yaml
Street Address: "Street Address"
ZIP/Postal Code: "ZIP/Postal Code"
City: "City"
Country: "Country"
Address Information: "Address Information"
```

## Usage for User Entity

If you want to add address fields to the User entity, first add the `SetAddressTrait` to the User entity, then use the same field configuration pattern in `UserCrudController`.
