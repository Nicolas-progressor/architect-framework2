# Blueprint VS Code Extension - Changelog

All notable changes to the Blueprint VS Code extension will be documented in this file.

## [1.0.0] - 2024-01-XX

### Added
- Initial release
- Syntax highlighting for `.blu` files
- Auto-closing pairs for `{% %}`, `{{ }}`, `{!! !!}`, `{# #}`
- 30+ snippets for common Blueprint constructs
- Language configuration (brackets, comments, indentation rules)
- Commands:
  - `blueprint.insertElement` - Insert element tag
  - `blueprint.insertVariable` - Insert variable output
  - `blueprint.wrapInBlock` - Wrap selection in block
- Support for embedded languages (HTML, CSS, JavaScript, PHP)
- Icon for `.blu` files

### Syntax Support
- Variables: `{{ variable }}`, `{!! variable !!}`
- Tags: `{% if %}`, `{% for %}`, `{% block %}`, etc.
- Comments: `{# comment #}`
- Filters: `{{ var|filter }}`
- Functions: `{{ func() }}`
- Operators: `and`, `or`, `not`, `in`, `is`, etc.

### Snippets
- Control structures: `if`, `ifelse`, `ifelseif`, `for`, `forkey`
- Template inheritance: `extends`, `block`, `include`
- Elements: `element`, `elementwith`
- Variables: `var`, `varraw`, `set`, `setblock`
- Filters: `filter`, `filterchain`
- Components: `html5`, `bscard`, `navbar`

---

## Future Plans

- [ ] IntelliSense for variables and functions
- [ ] Go to definition for blocks and templates
- [ ] Error detection and diagnostics
- [ ] Formatting support
- [ ] Emmet integration
- [ ] Color preview for color values
