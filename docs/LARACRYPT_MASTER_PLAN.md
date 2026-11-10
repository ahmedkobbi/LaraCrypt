# LaraCrypt — Master Prompt & Build Plan

## Context

**Why this document exists.** The user wants to build *LaraCrypt*: an advanced, modern, professional SaaS that obfuscates and protects PHP/Laravel enterprise codebases. The repo (`ahmedkobbi/LaraCrypt`) is currently greenfield (just a README). This file is the **master prompt (role + mission + phased plan)** the user asked for — the single source of truth an engineer or AI agent follows to build the product. No application code is written yet; this is the approved-before-build blueprint.

**Decisions locked with the user:**
- **Protection tier:** *Hybrid-max* — source obfuscation **+** encrypted bytecode **+** a native PHP loader extension (ionCube/SourceGuardian-class).
- **Deliverable now:** this master plan only. Build begins after approval.
- **Tenancy/deploy:** cloud SaaS serving both **B2B** (teams, SSO, on-prem option later) and **B2C** (individual devs).
- **Database:** **MySQL** (8.4 LTS).
- **Product name:** **LaraCrypt** (confirmed).

**Honest engineering stance (baked into the whole plan).** PHP is interpreted, so any protection that keeps code *runnable* can, in principle, be reverse-engineered with enough effort. "Unbreakable" and "mathematically guaranteed zero bugs" are not real deliverables and we will not claim them. What we *will* deliver is **maximum real-world cost-to-break** (defense in depth: obfuscation → encrypted bytecode → native loader → anti-tamper/licensing) and **engineering rigor that drives bugs and vulnerabilities toward zero** (typed code, exhaustive tests, fuzzing, security review, staged rollout). Marketing and product copy must reflect this honestly to avoid legal exposure and loss of trust.

---

## PART 1 — THE ROLE (system persona for the builder)

> **You are the Principal Engineer & Security Architect for LaraCrypt.**
>
> You combine four deep specialties:
> 1. **PHP/Zend internals engineer** — you know the Zend Engine, `op_array`, `zend_compile_file`, `zend_extension` hooks, opcode layout, and how these structures differ across PHP 8.1 → 8.5. You can write C extensions (NTS/ZTS, Linux/macOS/Windows).
> 2. **Compiler / program-transformation expert** — you work on ASTs (nikic/PHP-Parser), control-flow graphs, SSA, data-flow analysis, and semantics-preserving transformations. You design *provably behavior-preserving* obfuscation passes.
> 3. **Senior Laravel/SaaS architect** — latest Laravel, DDD-ish modular monolith, queues, multi-tenancy, billing, SSO, API design, observability.
> 4. **Application & product security engineer** — threat modeling, sandboxing untrusted uploaded code, supply-chain hardening, secrets management, OWASP ASVS.
>
> **Operating principles (non-negotiable):**
> - **Correctness first.** A transformation that changes program behavior is a bug of the highest severity. Every obfuscation pass must pass a *semantic-equivalence* test harness before it ships.
> - **Security of OUR platform is paramount.** We ingest untrusted customer source code and execute tooling over it. Treat every upload as hostile. Isolate, sandbox, never `eval`, never run customer code during obfuscation.
> - **Determinism + polymorphism.** Builds are reproducible given a seed, yet each customer build is polymorphically unique.
> - **No absolute claims.** Describe protection as "raises cost-to-break," never "unbreakable."
> - **Typed, tested, reviewed.** PHP at `declare(strict_types=1)`, PHPStan/Psalm max level, ≥90% meaningful coverage on the engine, C code fuzzed and ASAN-clean.
> - **Ship in honest tiers.** Don't fake strength; label each tier's real protection level.
>
> You think like an attacker to build the defender. You never ship a pass you cannot prove safe.

---

## PART 2 — THE MASTER PROMPT (mission brief)

**Mission:** Build LaraCrypt, a cloud SaaS that takes a PHP/Laravel project (upload or Git connect) and returns a protected build whose logic is expensive to recover, that runs identically to the original on the customer's PHP runtime, across PHP 8.1–8.5 and Laravel 9–13+ (dynamic version detection), with a dashboard, API, CLI, CI integrations, licensing, and billing for B2B + B2C.

**Core capabilities to deliver:**
1. **Ingest** a project (ZIP upload, or GitHub/GitLab/Bitbucket connect), detect PHP + Laravel version, map the dependency graph, and classify files (app code vs. vendor vs. config vs. views/blade vs. assets).
2. **Protect** with a layered pipeline (obfuscate → encode/encrypt → optionally compile to custom VM bytecode) using our own proprietary passes, configurable per project with sane presets.
3. **Loader** — produce an output that only runs when the LaraCrypt loader extension is present; bind to license (domain/hardware/expiry), with graceful, clear errors.
4. **Deliver** a downloadable artifact + checksum + a per-build report (what was protected, excluded, compatibility notes, install instructions for the loader).
5. **Operate** as a SaaS: accounts, teams, roles, projects, build history, quotas, billing, licensing portal, audit log, API, webhooks.

**Hard constraints:**
- Output must be **runtime-identical** to input (verified automatically).
- Must **not** break reflection-dependent framework behavior (Eloquent, container binding, route/config/event discovery, service providers, facades, queued job serialization, Blade compilation) — this is the hardest part and gets a dedicated compatibility layer.
- Zero data loss; customer source is encrypted at rest and deleted on a strict retention policy (or never stored, in CLI-first/on-prem modes later).
- No telemetry on customer source contents.

---

## PART 3 — TECH STACK (latest as of Nov 2026)

- **Language/runtime:** PHP **8.4** primary (8.3 supported, **8.5-ready**). `declare(strict_types=1)` everywhere.
- **Framework:** **Laravel 13** (latest stable in 2026; architected to adopt Laravel 14 when it ships Q1 2027).
- **Engine libraries:** `nikic/PHP-Parser` (AST), custom CFG/data-flow layer on top. `php-ast` where faster parsing is needed.
- **Loader extension:** C (Zend extension API), built per PHP version × platform × NTS/ZTS matrix. Build via `phpize`/CI matrix; distribute prebuilt `.so`/`.dll`/`.dylib`.
- **Frontend:** Laravel + **Inertia + React (TypeScript)** or **Livewire 3** (decide in Phase 0; React recommended for a polished B2B dashboard), **Tailwind**, Vite.
- **Async:** **Laravel Horizon** + Redis queues for the obfuscation workers; jobs run in **isolated sandbox containers**.
- **Data:** **MySQL 8.4 LTS (primary)** — the chosen database, accessed via Laravel Eloquent/migrations so a later Postgres option stays open. Redis (cache/queue), S3-compatible object storage (encrypted artifacts), optional ClickHouse for analytics later.
- **Infra:** Docker + Kubernetes; isolated, network-egress-denied worker pods for obfuscation; autoscaling.
- **Billing:** **Laravel Cashier (Stripe)** — subscriptions, trials, seats/usage metering, invoices, tax, webhooks, customer portal for both B2C and B2B.
- **Auth:** Laravel Fortify/Sanctum (API tokens) + SSO (SAML/OIDC) for B2B; 2FA.
- **Observability:** OpenTelemetry, Sentry, structured logs, Prometheus/Grafana.
- **Quality gates:** Pest/PHPUnit, PHPStan + Psalm (max), Laravel Pint, Rector, infection (mutation testing); C: Valgrind/ASAN + libFuzzer; Playwright E2E; GitHub Actions CI.

---

## PART 4 — PROTECTION ENGINE ARCHITECTURE (the core IP)

A **4-layer defense-in-depth pipeline**. Each layer is independently toggleable and independently tested for semantic equivalence.

### Layer 0 — Analysis & Compatibility Mapping
- Parse all PHP to AST; build a symbol table, call graph, and class/trait/interface graph.
- **Reflection-safety analyzer** (critical): detect symbols that must NOT be renamed — anything resolved by string at runtime: Eloquent model/column/relationship names, route/controller references, config keys, container bindings, event/listener class strings, Blade component names, `__invoke`, magic methods, framework contracts, serialized class names in queues, API resource keys, migration/factory references. Produce a **rename-exclusion set**. Conservative by default; allowlist widening is opt-in.
- Detect PHP + Laravel version and feature usage (enums, attributes, first-class callables, readonly, fibers) to pick version-correct transforms.

### Layer 1 — Source Obfuscation (AST passes) — *proprietary pass suite*
Semantics-preserving, each seeded by a per-build key for **polymorphism**:
- **Scope-aware identifier renaming** — locals, private props/methods, non-public symbols → opaque names, respecting the exclusion set from Layer 0.
- **String & literal protection** — encrypt/encode string and numeric literals; decode via per-build runtime helper with key derived at load.
- **Control-flow flattening** — convert structured control flow into a dispatcher/state-machine form with **opaque predicates** (algebraic identities that are hard to simplify statically).
- **Dead/junk code & bogus-path injection** — plausible but unreachable branches guarded by opaque predicates.
- **Expression & constant rewriting** — mixed boolean arithmetic (MBA), constant unfolding, call indirection (direct calls → dynamic dispatch tables).
- **Comment/whitespace/metadata stripping**, and docblock removal (configurable; keep where reflection needs them).
- **Anti-pattern normalization** so output can't be trivially pretty-printed back to clean code.

> *"Our own private algorithms"* live here and in Layer 3: the exact opaque-predicate families, the MBA rewriting tables, the flattening dispatcher design, the string-cipher/key-derivation scheme, and the polymorphism seeding are LaraCrypt-proprietary and versioned internally (never documented publicly, never shipped in readable form).

### Layer 2 — Encoding / Encryption (bytecode)
- Compile the transformed AST to an **intermediate bytecode** (either serialized Zend `op_array` snapshots per PHP version, or our own IR), then **encrypt** with a per-build/per-license key.
- Prepend a **magic header** so the loader recognizes LaraCrypt-encoded files; strip readable PHP source entirely.
- Key management: build-time key wrapped by license key; runtime key derived inside the loader (never present in plaintext on disk).

### Layer 3 — Native Loader Extension (the enforcement boundary) — *proprietary*
- A **Zend `zend_extension`** (C) that hooks `zend_compile_file` (and `include`/`require` resolution): on an encoded file, it **decrypts → reconstructs `op_array` → hands it to the engine**, bypassing source compilation.
- **Strongest optional sub-tier:** a **custom register/stack VM** embedded in the loader; selected functions are transpiled to LaraCrypt VM bytecode and interpreted natively, so even dumping Zend opcodes doesn't recover the original logic (accepting a performance trade-off on hot paths — user picks which modules).
- **Anti-tamper & licensing:** integrity/signature checks, anti-debug, license binding (domain / hardware fingerprint / expiry / trial time-bomb), tamper → clear fail-closed error.
- **Multi-version matrix:** build the loader for PHP 8.1/8.2/8.3/8.4/8.5 × {linux-x86_64, linux-arm64, musl, windows, macos} × {NTS, ZTS}. A per-version abstraction layer isolates Zend-internal struct differences. This matrix is the largest sustained engineering cost — budget for it.

### Semantic-equivalence harness (gate for every layer)
- A corpus of real PHP/Laravel apps + synthetic edge cases. For each: run the original and the protected build against an identical test suite / golden I/O, under every supported PHP version, and assert **identical observable behavior** (output, DB effects, exceptions, serialization). **No pass ships until green across the matrix.** This is how we attack "I don't want any bug/error."

---

## PART 5 — SaaS PLATFORM ARCHITECTURE

**Modular monolith (Laravel)**, domains:
- **Identity & Org** — users, teams/orgs, roles (owner/admin/developer/viewer), 2FA, SSO (SAML/OIDC) for B2B, API tokens.
- **Projects** — create project, connect Git or upload, configure protection preset, per-path include/exclude rules, environment profile (PHP/Laravel version).
- **Builds** — queue a build → sandboxed worker runs the engine → produce artifact + report + checksum; full build history, diff of settings, re-run.
- **Licensing** — issue/revoke license keys, bind to domains/hardware, expiry/trials, seat management; customer-facing license portal; loader phones nothing home by default (offline-validatable), optional online activation.
- **Billing** — **Laravel Cashier (Stripe)**: B2C plans (free/trial, pro) + B2B (seats + usage/overage, invoices, tax, POs), webhook-driven entitlements.
- **Delivery & Integrations** — download, signed URLs, API, webhooks, **CLI** (`laracrypt build`), **CI** (GitHub Action, GitLab template) that pulls settings and emits protected artifacts in-pipeline.
- **Admin/Back-office** — quotas, abuse controls, support impersonation (audited), feature flags.
- **Audit & Compliance** — immutable audit log, data-retention automation, DPA/GDPR tooling.

**The worker sandbox (security-critical):** obfuscation runs in **ephemeral, network-egress-denied, least-privilege containers**; customer code is **never executed**, only parsed/transformed; resource limits (CPU/mem/time) to stop parser DoS; artifacts encrypted and written to object storage; source purged per retention policy.

---

## PART 6 — PHASED BUILD ROADMAP

Each phase ends with: green CI, tests, docs, and a demo. Work lands on branch `claude/laravel-php-obfuscator-saas-xwtpkr`.

- **Phase 0 — Foundations & spikes (de-risk first).**
  - Repo scaffolding (Laravel 13, CI, static analysis, Pint, Pest), ADRs, threat model, this plan committed.
  - **Spike A:** prove the loader concept — minimal C `zend_extension` that intercepts a file with our magic header and runs precompiled opcodes on **one** PHP version. *This is the make-or-break risk; do it before anything else.*
  - **Spike B:** prove the semantic-equivalence harness on a trivial app.
  - Decide Inertia/React vs. Livewire.
- **Phase 1 — Obfuscation engine MVP (source layer).** AST pipeline, Layer-0 reflection analyzer, renaming + string + stripping passes, equivalence harness on a real small Laravel app. CLI `laracrypt build` (local). *This alone is a shippable "pure obfuscation" tier.*
- **Phase 2 — Encoding + loader (hybrid core).** Layer-2 encoding, Layer-3 loader for PHP 8.3/8.4 (NTS/ZTS, linux). License binding v1. End-to-end: protected Laravel app boots and passes its tests.
- **Phase 3 — SaaS platform.** Auth/orgs/billing, project+build flows, sandboxed workers + Horizon, dashboard, artifact delivery, API.
- **Phase 4 — Hardening & advanced protection.** Control-flow flattening, opaque predicates, MBA, polymorphism; optional custom-VM tier; anti-tamper/anti-debug; fuzzing + mutation testing + external security review.
- **Phase 5 — Scale & breadth.** Full PHP 8.1–8.5 × platform × NTS/ZTS loader matrix; Git/CI integrations; SSO; quotas/analytics; docs & onboarding; (later) on-prem/air-gapped edition.

---

## PART 7 — "NO BUGS / NO VULNERABILITIES" STRATEGY (how we actually get there)

- **Correctness:** semantic-equivalence harness is a hard CI gate across the full PHP-version matrix; property-based + fuzzed inputs; golden-app corpus; mutation testing (infection) to prove tests bite.
- **Compatibility:** dedicated Laravel compatibility suite (Eloquent, container, routing, events, queues, Blade, serialization) run on every supported Laravel version; conservative rename-exclusion defaults.
- **Platform security:** untrusted-input threat model; sandboxed, egress-denied workers; never execute customer code; strict input limits (anti-DoS parsing); OWASP ASVS checklist; `security-review` on every PR touching ingestion/sandbox; secret scanning; dependency/supply-chain pinning + SCA; least-privilege infra.
- **Native-code safety:** C loader under ASAN/UBSAN/Valgrind + libFuzzer on the decoder/parser; signed releases; reproducible builds.
- **Process:** small PRs, mandatory review, CI must be green, staged rollout + canary, incident runbooks. We state residual risk honestly rather than claiming perfection.

---

## PART 7.5 — ADDITIONAL THINGS TO BUILD IN (gaps the first draft didn't cover)

These are important items you asked me to surface — most are real features/policies, not nice-to-haves.

**A. Debuggability of protected apps (high priority, often forgotten).**
- **Stack-trace symbolication / "source maps for PHP."** When obfuscated code throws in production, the trace is unreadable. Store the per-build **name-map** (original↔obfuscated) encrypted server-side and offer a **de-obfuscation service** (dashboard + API + Sentry-style integration) that maps a protected trace back to original symbols. This is a headline feature, not an afterthought.
- **Error-reporting bridge** so customers can forward obfuscated exceptions and get readable traces (scoped to their org).

**B. Abuse prevention & acceptable use (ethical + legal + reputational — mandatory).**
- An obfuscation service is attractive to **malware authors**. We need an **Acceptable Use Policy**, abuse reporting, light KYC on higher tiers/paid plans, velocity/anomaly limits, and the ability to suspend accounts. We will **not** position LaraCrypt as "hide anything, no questions asked." Document this stance explicitly; it protects the business and is the right thing to do.

**C. Leak tracing — per-customer watermarking.**
- Embed an **invisible, per-license watermark/fingerprint** in each build so a leaked protected artifact can be traced to the account it was issued to. Strong B2B selling point.

**D. Reversibility & safety nets.**
- Always retain the **original build** (encrypted) + a **one-click revert**; show a **protected-vs-original diff/report**; never let a customer end up with only an unrunnable artifact.
- **Source escrow (optional)** for enterprise continuity.

**E. Licensing depth.**
- Online **and** offline activation, **floating/seat** licenses, air-gapped license files, grace periods, clear revocation and **fail-closed** behavior, trial time-bombs, and a customer **license portal**. Decide phone-home policy (default: offline-validatable, no telemetry on code).

**F. Loader lifecycle & compatibility policy.**
- Versioned loader with a **documented backward-compat guarantee** for encoded files, auto-update channel, and a support matrix page. Define what happens when a customer upgrades PHP before the matching loader exists (fail-closed + clear message).

**G. Scope decisions to pin down in Phase 0 (per file type).**
- **Blade** (compiles to PHP — protect compiled output and/or precompile), **config/routes/.env** (don't break framework discovery), **vendor/Composer packages** (default: exclude; selective protection), **migrations/factories/seeders**, **JS/CSS assets** (out of scope or minify-only). Produce a clear "what we protect / what we don't / why" matrix.

**H. Key management & release integrity.**
- **HSM/KMS** for encryption + loader-signing keys; reproducible, signed loader releases; supply-chain hardening (SLSA-style provenance).

**I. Legal & compliance.**
- ToS, **DPA**, explicit **customer-owns-their-IP** clause, GDPR + data-retention automation, **export-control review** (encryption/obfuscation tooling can trigger export rules), SOC 2 roadmap for B2B, EU **data-residency** option.

**J. Pricing & packaging.**
- Tiers: Free/trial (B2C) → Pro → Team → Enterprise (SSO, on-prem, SLA). Meter by **builds/month, project size, protected-module count, VM-tier usage**. Overage + invoicing for B2B.

**K. Product-ops.**
- Status page, docs/onboarding site, in-app "it broke my app" troubleshooting flow, backups/DR, multi-region, product analytics (never on customer code), and a reference "golden app" corpus maintained as living compatibility tests.

**L. Performance transparency.**
- Per-build **benchmark report** (startup + hot-path impact) so customers choose protection strength per module with real numbers.

**M. Server-access hardening (ship with every protected build) — the `.htaccess` request, done honestly.**
- Ship a **hardened `.htaccess`** in the protected output: deny direct web access to encoded `.php` and loader files, block `.env`/`.git`/config/`storage`/`vendor`, disable directory listing and server signature, force the public/ document root, set security headers, and restrict methods.
- **Honest caveat:** `.htaccess` is **Apache-only and not "unbreakable"** — it's a web-access-control layer, not code protection, and is silently ignored by **Nginx/LiteSpeed**. So we also generate **equivalent Nginx (and Caddy) config snippets**, and the real code protection remains Layers 1–3 (obfuscation + encrypted bytecode + loader). `.htaccess` is defense-in-depth, not the defense.
- Provide a **deployment hardening checklist** (correct document root, file permissions, `display_errors=off`, opcache settings, disable risky functions) in the per-build report.

---

## PART 8 — RISKS & HONEST LIMITATIONS (surface these to the user up front)

1. **Loader multi-version matrix is the dominant long-term cost** — Zend internals shift across PHP versions; each new PHP release needs loader work. Phase 0 Spike A must validate feasibility before committing.
2. **Reflection-heavy Laravel features are the top correctness risk** — aggressive renaming breaks runtime string resolution. Mitigated by conservative defaults + the compatibility suite; "obfuscate everything" is not safe and we won't offer it blindly.
3. **Performance trade-off** — control-flow flattening and the custom VM cost runtime speed; make them opt-in per module with benchmarks in the report.
4. **No absolute protection** — determined reverse engineering of runnable code is always possible; we sell *cost-to-break* + licensing enforcement, and must market it that way (legal review of claims).
5. **Trust & data handling** — we ingest customers' crown-jewel source; encryption at rest, strict retention/deletion, no-execution guarantee, and a future on-prem/CLI-only mode for clients who can't send code off-site are essential to winning B2B.
6. **"Zero bugs" is a direction, not a guarantee** — we commit to rigor (above), not to impossibility.

---

## PART 9 — VERIFICATION (how each phase is proven)

- **Engine:** `./vendor/bin/pest` (unit/pass tests) + the semantic-equivalence harness run across the PHP-version matrix in CI; PHPStan/Psalm max clean; mutation score threshold met.
- **Loader:** build matrix in CI; ASAN/fuzz jobs green; a real Laravel app, once protected, boots and passes **its own** test suite on every target PHP version.
- **SaaS:** feature/HTTP tests + Playwright E2E for critical flows (signup → project → build → download → run protected app); billing tested against Stripe test mode; security-review + ASVS checklist on ingestion/sandbox.
- **Acceptance per phase:** a documented demo (CLI or dashboard) producing a protected build of a reference Laravel app that runs identically to the original.

---

## Immediate next step after approval
Start **Phase 0**: commit this plan, scaffold Laravel 13 + CI/quality gates, and execute **Spike A (loader proof-of-concept)** and **Spike B (equivalence harness)** — because the loader feasibility is the single biggest risk and must be proven before building the product around it.
