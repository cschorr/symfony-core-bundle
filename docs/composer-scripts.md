# Composer Scripts for Code Quality

This project includes convenient composer scripts to run various code quality tools.

## 📋 Available Scripts

### Code Style
```bash
# Check code style (dry-run, shows issues without fixing)
docker compose exec php composer cs-check

# Fix code style automatically
docker compose exec php composer cs-fix
```

### Static Analysis
```bash
# Run PHPStan static analysis
docker compose exec php composer phpstan

# Run PHP_CodeSniffer 
docker compose exec php composer phpcs
```

### Code Modernization
```bash
# Check for potential code upgrades (dry-run)
docker compose exec php composer rector-check

# Apply code upgrades automatically
docker compose exec php composer rector-fix
```

### Security & Validation
```bash
# Check for security vulnerabilities (ignores abandoned packages)
docker compose exec php composer security-audit

# Validate composer.json structure
docker compose exec php composer composer-validate
```

### Testing
```bash
# Run PHPUnit tests
docker compose exec php composer test
```

### Batch Operations
```bash
# Run all quality checks (without fixing)
docker compose exec php composer check-all

# Fix all auto-fixable issues
docker compose exec php composer fix-all
```

## 🔄 Integration with GitHub Actions

These scripts are also used in the automated GitHub workflows:
- **Code Quality** workflow runs `cs-check`, `phpstan`, `phpcs`, `rector-check`
- **Auto-Fix Code Style** workflow runs `cs-fix` on pull requests

## 💡 Usage Tips

1. **Before committing**: Run `composer check-all` to catch issues early
2. **Auto-fix issues**: Use `composer fix-all` to automatically resolve style and upgrade issues
3. **CI/CD**: The GitHub workflows use the same scripts for consistency

## 🛠 Tool Configuration

- **PHP CS Fixer**: Configured in `.php-cs-fixer.dist.php`
- **PHPStan**: Configured in `phpstan.dist.neon` (level 8)
- **PHP_CodeSniffer**: Follows PSR-12 standards
- **Rector**: Configured in `rector.php`

## 🚨 Error Handling

If any script returns a non-zero exit code, it indicates issues were found:
- **Code style scripts**: Files need formatting or contain style violations
- **Static analysis**: Type errors or code quality issues detected
- **Security audit**: Vulnerable dependencies found
- **Tests**: Test failures occurred

Run individual scripts to get detailed information about specific issues.

## 📦 Package Notes

### Abandoned Packages
The `security-audit` script uses `--abandoned=ignore` to focus only on security vulnerabilities and ignore abandoned package warnings. Currently, there's one abandoned package:

- `behat/transliterator` - Required by `gedmo/doctrine-extensions` (StofDoctrineExtensionsBundle)
- No replacement suggested by maintainers
- Not a security risk, just unmaintained

This configuration ensures CI/CD pipelines don't fail on abandoned packages that have no security implications.