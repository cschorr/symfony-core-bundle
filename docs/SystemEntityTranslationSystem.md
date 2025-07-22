# SystemEntity Translation System

## Overview

This project uses a simple, direct translation approach for systemEntity navigation and UI elements.

## How it works

### 1. SystemEntity Entity Structure
- **`code` field**: Singular form (e.g., "User", "Company", "Project")  
- **`name` field**: Plural form (e.g., "Users", "Companies", "Projects")

### 2. Navigation Translation
The DashboardController uses the systemEntity's `name` field directly as translation key:

```php
$systemEntityNamePlural = $systemEntity->getName();  // e.g., "Users"
$label = $this->translator->trans($systemEntityNamePlural);
```

### 3. Translation Files

#### German (messages.de.yaml)
```yaml
# Singular forms (for forms, page titles, etc.)
User: "Benutzer"
Company: "Unternehmen"  
Project: "Projekt"

# Plural forms (for navigation)
Users: "Benutzer"
Companies: "Unternehmen"
Projects: "Projekte"
```

#### English (messages.en.yaml)
```yaml
# Singular forms
User: "User"
Company: "Company"
Project: "Project"

# Plural forms (for navigation)
Users: "Users"
Companies: "Companies"
Projects: "Projects"
```

### 4. Fixtures Configuration

The AppFixtures defines systemEntitys with correct singular/plural forms:

```php
$systemEntitys = [
    ['name' => 'Users', 'code' => 'User', 'text' => '...', 'icon' => '...'],
    ['name' => 'Companies', 'code' => 'Company', 'text' => '...', 'icon' => '...'],
    ['name' => 'Projects', 'code' => 'Project', 'text' => '...', 'icon' => '...'],
];
```

## Benefits

✅ **Simple**: No complex translation service needed  
✅ **Direct**: SystemEntity data directly drives translations  
✅ **Consistent**: Singular/plural forms clearly separated  
✅ **Maintainable**: Easy to add new systemEntitys  
✅ **Multilingual**: Easy to extend for new languages  

## Adding New SystemEntitys

1. Add systemEntity to `AppFixtures.php` with correct `name` (plural) and `code` (singular)
2. Add translations for both forms in all translation files
3. The navigation will automatically use the plural form

## Example: Adding a "Task" module

1. **Fixtures**:
   ```php
   ['name' => 'Tasks', 'code' => 'Task', 'text' => 'Task management', 'icon' => 'fas fa-tasks']
   ```

2. **German translations**:
   ```yaml
   Task: "Aufgabe"      # Singular
   Tasks: "Aufgaben"    # Plural (navigation)
   ```

3. **English translations**:
   ```yaml
   Task: "Task"         # Singular  
   Tasks: "Tasks"       # Plural (navigation)
   ```

The system automatically handles the rest!
