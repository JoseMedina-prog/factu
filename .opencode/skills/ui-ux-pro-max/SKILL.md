---
name: ui-ux-pro-max
description: UI/UX design intelligence expert for Laravel applications. Use when designing interfaces, selecting color palettes, typography, visual styles, building landing pages, dashboards, or reviewing code for UX issues. Covers 50+ design styles, 97 color palettes, 57 font pairings, 99 UX guidelines. Laravel + Blade + Tailwind + Livewire + Vite specific. Trigger keywords: design, UI, UX, colors, typography, landing page, dashboard, layout, component, Tailwind, Blade, Livewire.
---

# UI/UX Pro Max - Laravel Design Intelligence Expert

Comprehensive UI/UX design guide optimized for Laravel applications with Blade templates, Tailwind CSS, Livewire, and Vite.

## Workflow

### Step 1: Analyze User Requirements
Extract from user request:
- **Product type**: SaaS, e-commerce, portfolio, dashboard, landing page, blog, etc.
- **Style keywords**: minimal, playful, professional, elegant, dark mode, etc.
- **Industry**: healthcare, fintech, gaming, education, beauty, etc.
- **Tech stack**: Laravel + Blade + Tailwind (default), Livewire, Vue, React, etc.

### Step 2: Generate Design System
Consult reference data below for:
- Recommended patterns and section layouts
- Primary visual style with effects
- Color palette (primary, secondary, CTA, background, text)
- Typography pairing (heading + body fonts with Google Fonts URL)
- Key effects and anti-patterns to avoid

### Step 3: Implementation Guidelines
Apply Laravel-specific best practices:
- Blade templates, components, layouts
- Tailwind CSS utilities, responsive breakpoints
- Livewire components, wire:model binding
- Vite asset compilation
- Laravel asset() and vite() helpers

### Step 4: Pre-Delivery Verification
Run through all checklists before delivering any UI code.

---

## Priority Rules

| Priority | Category | Impact |
|----------|----------|--------|
| 1 | Accessibility | CRITICAL - Color contrast 4.5:1, focus states, aria-labels |
| 2 | Touch& Interaction | CRITICAL - 44x44px touch targets, cursor-pointer |
| 3 | Performance | HIGH - Image optimization, lazy loading, Vite builds |
| 4 | Layout & Responsive | HIGH - Mobile-first, viewport meta |
| 5 | Typography & Color | MEDIUM - Line height 1.5-1.75, readable fonts |
| 6 | Animation | MEDIUM - 150-300ms duration, transform-only |
| 7 | Style Selection | MEDIUM - Match style to product type |

---

## Quick Style Selector by Product Type

| Product Type | Primary Style | Secondary Styles | Color Mood |
|-------------|---------------|-----------------|------------|
| SaaS (General) | Minimalism | Flat Design, Glassmorphism | Trust blue + accent contrast |
| E-commerce | Neo-Brutalism | Bento Grid, Dark Mode | Brand primary + success green |
| E-commerce Luxury | Luxury Minimal | Dark Mode, Elegant | Premium black + gold accents |
| Healthcare | Soft Minimalism | Flat Design, Organic | Calm blue + health green |
| Fintech/Crypto | Cyberpunk | Dark Mode, Glassmorphism | Dark tech + vibrant accents |
| Gaming | Neon Glow | Cyberpunk, Dark Mode | Vibrant + neon + immersive |
| Portfolio/Creative | Editorial | Brutalism, Asymmetric | Brand primary + artistic |
| Dashboard | Corporate Tech | Flat Design, Data-Dense | Cool→Hot gradients + neutral |
| AI/Chatbot | Aurora Gradients | Glassmorphism, Dark Mode | Neutral + AI Purple (#6366F1) |
| Beauty/Spa/Wellness | Organic Shapes | Soft Minimalism, Elegant | Soft pastels + natural tones |
| Landing Page (Books/Courses) | Editorial | Soft Minimalism, Trust | Warm neutrals + accent CTA |

---

## Color Palettes by Industry

### SaaS / Business
| Role | Hex | Tailwind |
|------|-----|----------|
| Primary | #2563EB | blue-600 |
| Secondary | #3B82F6 | blue-500 |
| CTA | #F97316 | orange-500 |
| Background | #F8FAFC | slate-50 |
| Text | #1E293B | slate-800 |
| Border | #E2E8F0 | slate-200 |

### E-commerce / Books
| Role | Hex | Tailwind |
|------|-----|----------|
| Primary | #7C3AED | violet-600 |
| Secondary | #A78BFA | violet-400 |
| CTA | #F97316 | orange-500 |
| Background | #FEFCE8 | amber-50 |
| Text | #1E293B | slate-800 |
| Border | #E2E8F0 | slate-200 |

### Healthcare / Wellness
| Role | Hex | Tailwind |
|------|-----|----------|
| Primary | #0891B2 | cyan-600 |
| Secondary | #22D3EE | cyan-400 |
| CTA | #059669 | emerald-600 |
| Background | #ECFEFF | cyan-50 |
| Text | #164E63 | cyan-900 |
| Border | #A5F3FC | cyan-200 |

### Fintech / Crypto
| Role | Hex | Tailwind |
|------|-----|----------|
| Primary | #F59E0B | amber-500 |
| Secondary | #FBBF24 | amber-400 |
| CTA | #8B5CF6 | violet-500 |
| Background | #0F172A | slate-900 |
| Text | #F8FAFC | slate-50 |
| Border | #334155 | slate-700 |

### Beauty / Spa / Wellness
| Role | Hex | Tailwind |
|------|-----|----------|
| Primary | #DB2777 | pink-600 |
| Secondary | #F472B6 | pink-400 |
| CTA | #059669 | emerald-600 |
| Background | #FDF2F8 | pink-50 |
| Text | #831843 | pink-900 |
| Border | #FBCFE8 | pink-200 |

### Landing Page / Books (Warm)
| Role | Hex | Tailwind |
|------|-----|----------|
| Primary | #92400E | amber-800 |
| Secondary | #B45309 | amber-700 |
| CTA | #DC2626 | red-600 |
| Background | #FEF3C7 | amber-100 |
| Text | #1C1917 | stone-900 |
| Border | #FDE68A | amber-200 |

---

## Typography Pairings

### Professional / Corporate
**Heading**: Inter | **Body**: Inter
```css
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
```
Best for: SaaS, dashboards, business apps

### Elegant / Luxury
**Heading**: Playfair Display | **Body**: Lato
```css
@import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Lato:wght@400;700&display=swap');
```
Best for: Luxury brands, editorial, high-end e-commerce

### Modern / Tech
**Heading**: Space Grotesk | **Body**: DM Sans
```css
@import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=DM+Sans:wght@400;500;700&display=swap');
```
Best for: Tech startups, fintech, AI products

### Friendly / Playful
**Heading**: Poppins | **Body**: Open Sans
```css
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&family=Open+Sans:wght@400;600&display=swap');
```
Best for: Education, consumer apps, lifestyle brands

### Editorial / Creative
**Heading**: Fraunces | **Body**: Source Sans 3
```css
@import url('https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600;700&family=Source+Sans+3:wght@400;600&display=swap');
```
Best for: Blogs, magazines, portfolios

### Healthcare / Wellness
**Heading**: Outfit | **Body**: Nunito
```css
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@500;600;700&family=Nunito:wght@400;600&display=swap');
```
Best for: Healthcare, wellness, medical apps

### Warm / Books
**Heading**: Merriweather | **Body**: Source Sans3
```css
@import url('https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Source+Sans+3:wght@400;600&display=swap');
```
Best for: Books, reading, literary content

---

## Visual Styles Reference

### Minimalism
- **Colors**: Monochrome + 1 accent, high whitespace
- **Effects**: Subtle shadows, clean borders, micro-interactions
- **Best For**: SaaS, productivity, professional apps
- **Keywords**: clean, simple, whitespace, subtle, elegant

### Glassmorphism
- **Colors**: Translucent layers, blur effects, gradient backgrounds
- **Effects**: `backdrop-blur`, `bg-white/10`, soft shadows
- **Best For**: Modern dashboards, music apps, portfolios
- **Keywords**: glass, blur, translucent, frosted, layered

### Neumorphism
- **Colors**: Soft pastels, same-hue shadows (dark/light)
- **Effects**: Inset shadows, extruded elements, soft depth
- **Best For**: Control panels, calculators, music players
- **Keywords**: soft, embossed, extruded, tactile, 3D-soft

### Dark Mode
- **Colors**: Dark backgrounds (#0F172A), light text, accent colors
- **Effects**: Subtle borders, glows on focus, reduced eye strain
- **Best For**: Developer tools, media apps, gaming
- **Keywords**: dark, night, low-light, contrast, modern

### Bento Grid
- **Colors**: Card-based with distinct backgrounds per section
- **Effects**: Grid layouts, varying card sizes, clear hierarchy
- **Best For**: Feature showcases, portfolios, dashboards
- **Keywords**: grid, cards, modular, organized, showcase

### Aurora / Gradient Mesh
- **Colors**: Multi-color gradients, mesh blending, vibrant
- **Effects**: Animated gradients, color transitions, depth
- **Best For**: AI products, creative tools, landing pages
- **Keywords**: aurora, gradient, mesh, colorful, flowing

### Brutalism
- **Colors**: High contrast, raw colors, bold primaries
- **Effects**: Heavy borders, raw typography, intentionally rough
- **Best For**: Creative agencies, art portfolios, bold brands
- **Keywords**: raw, bold, stark, unpolished, striking

### Editorial
- **Colors**: Serif headings, generous whitespace, magazine-style
- **Effects**: Large typography, asymmetric layouts, visual rhythm
- **Best For**: Blogs, magazines, portfolios, books
- **Keywords**: editorial, magazine, serif, spacious, literary

---

## Landing Page Patterns

### Hero-Centric (Conversion Focus)
**Sections**: Hero > Social Proof > Features > CTA
- Primary CTA above fold
- Trust badges near CTA
- Single focused message

### Feature-Forward (Product Demo)
**Sections**: Hero > Feature Grid > How It Works > Pricing > CTA
- Visual product demos
- Benefits over features
- Comparison tables for pricing

### Story-Driven (Brand Building)
**Sections**: Hero > Problem > Solution > Journey > Team > CTA
- Emotional connection first
- Customer success stories
- Brand narrative flow

### Social Proof Heavy (Trust Building)
**Sections**: Hero > Logos > Testimonials > Case Studies > CTA
- Client logos prominent
- Video testimonials
- Stats and numbers

### Book/Product Launch
**Sections**: Hero > About the Book > What You'll Learn > Testimonials > CTA > Author Bio
- Book cover as hero visual
- Problem/solution framing
- Clear benefits list
- Author credibility
- Limited time offer urgency

---

## Chart Type Selection

| Data Type | Best Chart | When to Use |
|-----------|------------|--------------|
| Trend Over Time | Line Chart | Time-series, growth, progress |
| Compare Categories | Bar Chart | Rankings, comparisons |
| Part-to-Whole | Donut Chart | Percentages, proportions (≤5 items) |
| Correlation | Scatter Plot | Relationships, patterns |
| Geographic | Choropleth Map | Regional data, locations |
| Funnel/Flow | Funnel Chart | Conversion, process stages |
| Performance | Gauge/Bullet | KPIs, targets |
| Hierarchical | Treemap | Nested categories, proportions |

**Chart Color Guidance**:
- Primary data: #2563EB (blue-600)
- Success/Growth: #22C55E (green-500)
- Warning/Alert: #F59E0B (amber-500)
- Error/Decline: #EF4444 (red-500)
- Neutral: #94A3B8 (slate-400)

---

## Laravel-Specific Guidelines

### Blade Templates
- Use `{{ $variable }}` for escaping output
- Use `{!! $html !!}` only for trusted HTML
- Use `@csrf` for forms to prevent CSRF
- Use `@method('PUT'|'DELETE')` for method spoofing
- Use `@auth` and `@guest` directives for auth checks
- Use `{{ route('name') }}` for route URLs
- Use `{{ asset() }}` for public assets
- Use `{{ storage_path() }}` for storage files

### Blade Components
- Use `x-component` syntax for Blade components
- Use `{{ $slot }}` for component content
- Use `{{ $attributes }}` for HTML attributes
- Use `wire:model` for Livewire binding
- Use `x-data` for Alpine.js interactivity

### Tailwind CSS in Laravel
- Use `@tailwind` directives in CSS files
- Use `vite()` helper in Blade for Vite assets
- Use `asset()` helper for compiled assets
- Use `storage:link` for storage symlinks
- Responsive: `sm:`, `md:`, `lg:`, `xl:`, `2xl:`
- Dark mode: `dark:` variant

### Vite + Laravel
- Input files: `resources/css/app.css`, `resources/js/app.js`
- Use `@vite` directive for dev mode (localhost)
- Use `asset('build/...')` for production builds
- Run `npm run dev` for development
- Run `npm run build` for production

### Livewire Best Practices
- Use `wire:model` for two-way binding
- Use `wire:click` for event handling
- Use `wire:init` for component initialization
- Use `wire:loading` and `wire:target` for loading states
- Use `#[Rule('required')]` for validation
- Use `$this->validate()` in method
- Use `Session::flash()` for flash messages

### Asset Organization
```
resources/
 css/
    app.css          # Main styles
  js/
    app.js           # Main scripts
  views/
    layouts/         # Layout templates
    components/ # Blade components
    partials/       # Partial templates

public/
  build/
    assets/
      app-xxx.css    # Compiled CSS
      app-xxx.js     # Compiled JS
```

---

## Accessibility Requirements (CRITICAL)

### Color Contrast
- Normal text: 4.5:1 minimum ratio
- Large text (18px+): 3:1 minimum ratio
- Interactive elements: Clear focus states

### Touch Targets
- Minimum size: 44x44px for touch devices
- Spacing: 8px minimum between targets

### Focus States
```css
:focus-visible {
  outline: 2px solid #2563EB;
  outline-offset: 2px;
}
```

### Reduced Motion
```css
@media (prefers-reduced-motion: reduce) {
  *, *::before, *::after {
    animation-duration: 0.01ms !important;
    transition-duration: 0.01ms !important;
  }
}
```

### Form Labels
- Every input MUST have associated label
- Use `<label for="id">` or `aria-label`
- Error messages near the problem field

### Images
- All images have `alt` text
- Decorative images use `alt=""`

---

## Common UX Anti-Patterns (AVOID)

### Icons
- **NO**: Using emojis as UI icons
- **YES**: Use SVG icons (Heroicons, Lucide, Simple Icons)

### Hover States
- **NO**: Scale transforms that shift layout
- **YES**: Color/opacity transitions only

### Cursors
- **NO**: Default cursor on clickable elements
- **YES**: `cursor-pointer` on all interactive elements

### Light Mode Glass
- **NO**: `bg-white/10` (too transparent, invisible)
- **YES**: `bg-white/80` or higher opacity

### Z-Index
- **NO**: Random z-index values (999, 9999)
- **YES**: Defined scale: 10 (default), 20 (dropdown), 30 (modal), 50 (toast)

### Animations
- **NO**: Slow animations (>500ms) or layout-shifting
- **YES**: 150-300ms duration, transform/opacity only

### Laravel Specific
- **NO**: Inline styles or `<style>` tags in Blade
- **YES**: Tailwind utilities or proper CSS files
- **NO**: Raw SQL queries for display
- **YES**: Eloquent relationships and accessors
- **NO**: Unescaped user input `{!! !!}`
- **YES**: `{{ }}` for all user content

---

## Pre-Delivery Checklist

### Visual Quality
- [ ] No emojis as icons (use SVG: Heroicons, Lucide, Simple Icons)
- [ ] All icons from consistent icon set
- [ ] Brand logos verified from official sources
- [ ] Hover states don't cause layout shift
- [ ] Consistent spacing using Tailwind scale

### Interaction
- [ ] All clickable elements have `cursor-pointer`
- [ ] Hover states provide clear visual feedback
- [ ] Transitions are smooth (150-300ms)
- [ ] Focus states visible for keyboard navigation
- [ ] Loading states for async operations

### Light/Dark Mode
- [ ] Light mode text has sufficient contrast (4.5:1 minimum)
- [ ] Glass/transparent elements visible in light mode
- [ ] Borders visible in both modes
- [ ] `dark:` variant implemented where needed

### Layout
- [ ] Floating elements have proper spacing from edges
- [ ] No content hidden behind fixed navbars
- [ ] Responsive at 375px, 768px, 1024px, 1440px
- [ ] No horizontal scroll on mobile
- [ ] Proper use of Tailwind responsive prefixes

### Laravel Specific
- [ ] Assets load correctly in both dev (`@vite`) and production (`asset`)
- [ ] CSRF token present on all forms
- [ ] Routes use `route()` helper or named routes
- [ ] Flash messages styled appropriately
- [ ] Validation errors displayed near fields

### Accessibility
- [ ] All images have alt text
- [ ] Form inputs have labels
- [ ] Color is not the only indicator
- [ ] `prefers-reduced-motion` respected
- [ ] Keyboard navigation works
- [ ] Focus states visible

### Pre-Implementation Checklist
- [ ] Product type identified
- [ ] Visual style selected
- [ ] Color palette defined
- [ ] Typography pairing chosen
- [ ] Landing page pattern selected
- [ ] Accessibility requirements noted
- [ ] Tech stack guidelines reviewed

---

## Response Style

When designing interfaces:

1. Present the design system first with all recommendations
2. Show color swatches with hex values and usage
3. Include Google Fonts import code ready to use
4. Provide code examples following Laravel + Tailwind best practices
5. Explain design decisions with reasoning
6. Include pre-delivery checklist items

### Code Example Template

```blade
{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }}</title>

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=..." rel="stylesheet" />

    {{-- Assets: use @vite for dev, asset() for prod --}}
    @if (app()->environment('local'))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <link rel="stylesheet" href="{{ asset('build/app.css') }}">
        <script src="{{ asset('build/app.js') }}" defer></script>
    @endif
</head>
<body class="font-sans antialiased bg-slate-50 text-slate-800">
    {{ $slot }}
</body>
</html>
```

---

## Color Contrast Reference

### Light Mode
| Background | Text | Ratio | Pass |
|------------|------|-------|------|
| #FFFFFF | #1E293B | 15:1 | AAA |
| #F8FAFC | #1E293B | 14:1 | AAA |
| #F8FAFC | #475569 | 9:1 | AAA |
| #F8FAFC | #64748B | 7:1 | AA |
| #FEFCE8 | #92400E | 7:1 | AA |

### Dark Mode
| Background | Text | Ratio | Pass |
|------------|------|-------|------|
| #0F172A | #F8FAFC | 16:1 | AAA |
| #0F172A | #CBD5E1 | 12:1 | AAA |
| #0F172A | #94A3B8 | 8:1 | AA |
| #1E293B | #F8FAFC | 13:1 | AAA |

---

## Tailwind Color Reference

### Primary Blues
- `blue-50` (#EFF6FF) → `blue-950` (#172554)
- `blue-600` (#2563EB) - Primary action
- `blue-500` (#3B82F6) - Secondary

### Neutral Slates
- `slate-50` (#F8FAFC) - Light backgrounds
- `slate-800` (#1E293B) - Light text
- `slate-950` (#020617) - Dark backgrounds
- `slate-50` (#F8FAFC) - Dark text

### Accent Oranges
- `orange-500` (#F97316) - CTA buttons
- `amber-500` (#F59E0B) - Warnings/highlights

### Success/Error
- `green-500` (#22C55E) - Success states
- `red-500` (#EF4444) - Error states
- `emerald-600` (#059669) - Healthcare CTA

---

## Useful Resources

- [Tailwind CSS Docs](https://tailwindcss.com/docs)
- [Laravel Blade Docs](https://laravel.com/docs/blade)
- [Heroicons](https://heroicons.com)
- [Lucide Icons](https://lucide.dev)
- [Google Fonts](https://fonts.google.com)
- [WCAG Guidelines](https://www.w3.org/WAI/WCAG21/quickref/)
- [Contrast Checker](https://webaim.org/resources/contrastchecker/)
