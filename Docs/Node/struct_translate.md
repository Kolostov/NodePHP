# Translation Directory Structure

## Overview: Internationalization & Localization System

The Translation directory manages all language-specific content, following a resource-based approach where `.po` (Portable Object) and `.mo` (Machine Object) files are generated from framework resources. This system leverages the Gettext standard for comprehensive internationalization while maintaining tight integration with Node.php's resource generation patterns.

### Translation Philosophy in Node.php

- **Resource-based**: Translation files generated from framework resources via `cli_new`
- **Gettext standard**: Uses `.po`/`.mo` files for compatibility with translation tools
- **Automatic generation**: New resources automatically create corresponding translation files
- **Framework integration**: Works with validation patterns and structured logging
- **Locale isolation**: Each language in its own directory with complete file sets

## Translation Directory Components

### Translation/Locale

**Purpose**: Language-specific translation files organized by locale code, containing `.po` (source) and `.mo` (compiled) files for each resource. When a new resource is created via `cli_new`, corresponding translation files are automatically generated in all configured locales.

**File Structure**:

```
Translation/
├── Locale/
│   ├── en_US/                    # American English locale
│   │   ├── messages.po           # General application messages
│   │   ├── messages.mo           # Compiled messages
│   │   ├── validation.po         # Validation error messages
│   │   ├── validation.mo         # Compiled validation messages
│   │   ├── errors.po             # Error messages
│   │   ├── errors.mo             # Compiled error messages
│   │   └── {resourceName}.po     # Resource-specific translations
│   ├── fr_FR/                    # French locale
│   │   └── (same file structure)
│   └── es_ES/                    # Spanish locale
│       └── (same file structure)
```

**Generation Flow**:

```
cli_new creates resource → Generates .po files in all locales → Translators edit .po → .mo compiled for production
```

**Example .po File Structure**:

```po
# Translation/Locale/en_US/messages.po
msgid ""
msgstr ""
"Content-Type: text/plain; charset=UTF-8\n"
"Language: en_US\n"

msgid "welcome"
msgstr "Welcome to our application"

msgid "greeting"
msgstr "Hello, %s!"

msgid "items.count"
msgid_plural "%d items"
msgstr[0] "%d item"
msgstr[1] "%d items"
```

**Framework Integration**:

```php
// Setup Gettext in Node.php bootstrap
bindtextdomain('messages', ROOT_PATH . 'Translation/Locale');
textdomain('messages');

// Detect locale from session or request
$locale = $_SESSION['locale'] ??
          $request->getPreferredLanguage() ??
          env('NODE:APP_LOCALE', 'en_US');
setlocale(LC_ALL, "{$locale}.UTF-8");

// Usage throughout application
echo gettext('welcome'); // Returns translated string
echo sprintf(gettext('greeting'), $user->name); // With placeholder

// Pluralization
echo ngettext('%d item', '%d items', $count);
```

**Automatic File Generation**:
When `cli_new` creates a resource like `UserController`, it automatically:

1. Creates `usercontroller.po` in each locale directory under `Translation/Locale/`
2. Includes basic translation stubs for common methods/actions
3. Updates documentation with translation notes

```php
// Generated usercontroller.po when creating UserController
msgid "usercontroller.index.title"
msgstr "User List"

msgid "usercontroller.create.title"
msgstr "Create New User"

msgid "usercontroller.edit.title"
msgstr "Edit User"
```

**Key Characteristics**:

- **Automatic Generation**: `.po` files created with new resources
- **Locale Isolation**: Complete file sets per language
- **Gettext Standard**: Compatible with translation tools (Poedit, Transifex)
- **Pluralization Support**: Full Gettext plural rules
- **Context Support**: `msgctxt` for disambiguation

### Translation/Validation

**Purpose**: Specialized translation files for validation error messages. These are `.po` files specifically for form validation errors, with structured keys matching validation rule names.

**File Structure**:

```
Translation/
├── Validation/                 # Validation-specific translations
│   ├── en_US.po               # English validation messages
│   ├── en_US.mo               # Compiled English validation
│   ├── fr_FR.po               # French validation messages
│   ├── fr_FR.mo               # Compiled French validation
│   └── (additional languages)
```

**Validation .po File Example**:

```po
# Translation/Validation/en_US.po
msgid "validation.required"
msgstr "The %s field is required."

msgid "validation.email"
msgstr "The %s must be a valid email address."

msgid "validation.min.string"
msgstr "The %s must be at least %d characters."

msgid "validation.min.numeric"
msgstr "The %s must be at least %d."

msgid "validation.custom.email.required"
msgstr "We need your email address to continue."

# Attribute names (for %s replacement)
msgid "attributes.email"
msgstr "email address"

msgid "attributes.password"
msgstr "password"

msgid "attributes.first_name"
msgstr "first name"
```

**Framework Integration with Validator Pattern**:

```php
final class Validator
{
    private string $locale;

    public function __construct(string $locale = null)
    {
        $this->locale = $locale ?? $_SESSION['locale'] ?? env('NODE:APP_LOCALE', 'en_US');

        // Set locale for Gettext validation domain
        bind_textdomain_codeset('validation', 'UTF-8');
        bindtextdomain('validation', ROOT_PATH . 'Translation/Validation');
        textdomain('validation');
        setlocale(LC_ALL, "{$this->locale}.UTF-8");
    }

    public function addError(string $field, string $rule, array $parameters = []): void
    {
        // Get human-readable attribute name
        $attribute = dgettext('validation', "attributes.{$field}") ?? $field;

        // Construct message key
        $messageKey = "validation.{$rule}";

        // Get translated message
        $message = gettext($messageKey);

        // If custom message exists for this field.rule
        $customKey = "validation.custom.{$field}.{$rule}";
        $customMessage = dgettext('validation', $customKey);
        if ($customMessage !== $customKey) { // Gettext returns key if not found
            $message = $customMessage;
        }

        // Replace placeholders
        $replacements = array_merge([$attribute], $parameters);
        $message = vsprintf($message, $replacements);

        $this->errors[$field][] = $message;

        // Log validation error
        r("Validation failed", 'Validation', false, [
            'field' => $field,
            'rule' => $rule,
            'locale' => $this->locale,
            'message' => $message
        ]);
    }
}
```

**Usage in Form Patterns**:

```php
// In a controller handling form submission
$validator = new Validator($request->getLocale());

if (!$validator->validate($data, [
    'email' => 'required|email',
    'password' => 'required|min:8',
])) {
    // Errors are already translated to user's locale
    return response()->withErrors($validator->getErrors());
}

// Error messages shown to user are locale-specific:
// English: "The email address field is required."
// French:  "Le champ adresse email est obligatoire."
// Spanish: "El campo dirección de correo electrónico es obligatorio."
```

**Automatic Generation with Resources**:
When a new Form resource is created via `cli_new`, the system:

1. Generates validation rule definitions
2. Creates/updates `.po` files with field-specific custom messages
3. Documents validation rules in the resource documentation

```bash
# Creating a new registration form resource
php node.php new Form UserRegistration

# Generates:
# - Primitive/Form/UserRegistration.php
# - Translation/Locale/*/userregistration.po (for form labels)
# - Translation/Validation/*.po updates (for custom validation messages)
# - Docs/userregistration.md (documentation)
```

**Key Characteristics**:

- **Rule-based Messages**: Messages organized by validation rule
- **Attribute Mapping**: Human-readable field names in each language
- **Custom Overrides**: Field-specific message customization
- **Parameter Support**: Dynamic values in messages (min, max, etc.)
- **Automatic Updates**: New forms/fields update translation files

## Translation Workflow in Node.php

### 1. Resource Creation

```bash
# Creates resource and associated translation files
php node.php new Controller User
# Generates:
# - Class/Final/Presentation/Controller/User.php
# - Translation/Locale/en_US/user.po
# - Translation/Locale/fr_FR/user.po
# - Translation/Locale/es_ES/user.po
# - Docs/user.md
```

### 2. Translation Management

```bash
# Extract translatable strings from codebase
php node.php extract-translations

# Compile .po to .mo for production
php node.php compile-translations

# Update translation files from templates
php node.php update-translations
```

### 3. Development Integration

```php
// In code, use Gettext functions
echo gettext('user.index.title');

// With placeholders
printf(gettext('user.greeting'), $username);

// In templates
<h1><?= _('page.title') ?></h1>

// Validation errors (automatically locale-aware)
$errors = $validator->getErrors(); // Already translated
```

## Framework Integration Points

### With CLI Commands

```php
function _node_cli_extract_translations(): string
{
    // Extract strings from source code to .pot template
    $potFile = ROOT_PATH . 'Translation/template.pot';

    // Use xgettext or custom parser to find translatable strings
    $strings = $this->extractStringsFromCodebase();

    // Merge with existing translations
    $this->updatePoFiles($strings);

    return "Extracted " . count($strings) . " translatable strings.\n";
}
```

### With Documentation Generation

When `cli_new` creates documentation for a resource, it includes:

- Translation keys used by the resource
- Instructions for translators
- Context for each translatable string

### With Build Process

```php
// In deployment/build process
function compileTranslationsForProduction(): void
{
    foreach (glob('Translation/Locale/*/*.po') as $poFile) {
        $moFile = str_replace('.po', '.mo', $poFile);
        // Compile .po to .mo using msgfmt
        exec("msgfmt {$poFile} -o {$moFile}");

        r("Compiled translations", 'Build', null, [
            'po_file' => basename($poFile),
            'mo_file' => basename($moFile),
            'locale' => basename(dirname($poFile))
        ]);
    }
}
```

## Best Practices for Node.php Translations

1. **Use Descriptive Keys**: `controller.action.element` not generic names
2. **Context Comments**: Add comments in `.po` files for translators
3. **Plural Forms**: Define plural rules for each locale
4. **Regular Updates**: Run extraction after adding new strings
5. **Backup .po Files**: Keep `.po` files in version control, not `.mo`
6. **Fallback Chain**: Always have complete English translations
7. **Testing**: Verify translations in all supported locales

This translation system provides robust internationalization while maintaining the resource-based philosophy of Node.php, where new resources automatically get associated translation files that can be managed through the standard Gettext workflow.
