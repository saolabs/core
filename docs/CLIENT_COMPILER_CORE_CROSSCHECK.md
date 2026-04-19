# Core vs Client vs Compiler Crosscheck

Date: 2026-04-18
Scope: Marker shortcuts + directive mapping
Mode: Read-only audit notes, no auto-fix

## 1) Marker Shortcut Crosscheck (3-column)

| Marker key | Core (PHP) | Client (TS runtime) |
|---|---|---|
| view | v | v |
| component | c | c |
| layout | l | l |
| template | t | t |
| block | b | b |
| blockoutlet | bo | bo |
| reactive | r | r |
| section | s | s |
| fragment | frg | frg |
| for | fo | fo |
| forin | fi | fi |
| foreach | fe | fe |
| while | wh | wh |
| if | if | if |
| switch | sw | sw |
| include | inc | inc |
| echo | e | e |
| echoescaped | ee | ee |
| yield | y | y |
| slot | st | st |
| useblock | ub | ub |
| extend | ex | ex |
| style | sty | sty |
| script | sc | sc |
| output | o | MISSING |

### Marker Notes

- `output` exists in Core marker shortcut map but is not declared in Client MarkerRegistry shortcut map.
- Compiler runtime does generate output markers (`@startMarker('output', ...)`) in sao2blade hydration processor.
- This is the main marker mismatch that should be tracked.

## 2) Directive Mapping Crosscheck (3-column)

Legend:
- `Direct`: Exists with same name in that layer.
- `Mapped`: Present but mapped/translated to another directive name.
- `N/A`: Not expected at that layer.
- `Missing`: Expected by reference but not found in Core Blade directive registrations.

| Directive (compiler reference) | Core (Blade directives) | Compiler |
|---|---|---|
| @state | Mapped to `@useState` | Direct |
| @states | Direct (`@states`) | Direct |
| @props | Missing in current Core registrations | Direct |
| @let | Direct (`@let`) | Direct |
| @const | Direct (`@const`) | Direct |
| @vars | Not found in current Core registrations | Direct |
| @import | Missing in current Core registrations | Direct |
| @await | Missing in current Core registrations | Direct |
| @bind | Direct (`@bind`) | Direct |
| @out | Direct (`@out`) | N/A in reference table |
| @attr | Direct (`@attr`) | Direct |
| @class | Missing in current Core registrations | Direct |
| @style | Missing in current Core registrations | Direct |
| @show | Missing in current Core registrations | Direct |
| @disabled | Missing in current Core registrations | Direct |
| @click | Missing in current Core registrations | Direct |
| @input | Missing in current Core registrations | Direct |
| @change | Missing in current Core registrations | Direct |
| @submit | Missing in current Core registrations | Direct |
| @keydown | Missing in current Core registrations | Direct |
| @mouseenter | Missing in current Core registrations | Direct |
| @block | Direct (`@block`) | Direct |
| @endblock | Direct (`@endblock`) | Direct |
| @extends | Missing in current Core registrations (Laravel native directive still exists) | Direct |
| @forelse | Laravel native (not custom in Core) | Direct |
| @empty | Laravel native (not custom in Core) | Direct |
| @endforelse | Laravel native (not custom in Core) | Direct |
| @each | Laravel native (not custom in Core) | Direct |
| @foreach/@if/@while/etc | Laravel native (not custom in Core) | Direct |

### Directive Notes

- A large set in compiler docs is Sao-language/front-end compile level directives, while Core currently exposes a narrower Blade runtime directive set.
- Some entries are likely expected translation stages, not bugs, but should be documented for team clarity.
- `@out` is a Core-side runtime helper directive (one:output wrapper), so it is tracked explicitly even when not listed in compiler reference directives.

## 3) Compiler Runtime Marker Types Seen

Observed `@startMarker('<type>', ...)` types from compiler runtime:

- `reactive`
- `while`
- `component`
- `yield`
- `output`

Core marker shortcut map contains all above keys, but Client MarkerRegistry currently misses `output`.

## 4) Suggested Tracking Items (No Auto Changes)

1. Decide whether Client MarkerRegistry should add shortcut for `output`.
2. Decide whether Core should explicitly register more compiler-reference directives, or keep current split by layer and document it.
3. Keep a versioned matrix per release to avoid silent drift between Core/Client/Compiler.

## 5) Source Pointers

- Core marker map: `src/core/View/Services/ViewStorageManager.php`
- Core directive registrations: `src/core/View/Compilers/*DirectiveService.php`
- Client marker registry: `../client/dist/src/core/services/MarkerRegistry.js`
- Client marker type contract: `../client/dist/src/core/contracts/MarkerInterface.d.ts`
- Compiler directive reference: `../compiler/docs/DIRECTIVES-REFERENCE.md`
- Compiler runtime marker emission: `../compiler/src/sao2blade/hydrate_processor.py`
