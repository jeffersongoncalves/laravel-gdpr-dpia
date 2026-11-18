# Changelog

All notable changes to this project will be documented in this file.

## 1.1.0 - 2026-11-18

### Changed

- Requires the 2.0 of the suite core, which runs on [laravel-privacy](https://github.com/jeffersongoncalves/laravel-privacy), the engine shared by the privacy suites. No change to this package's API, config or tables.

## 1.0.0 - 2026-11-17

First release: GDPR data protection impact assessments (Art. 35) for Laravel, part of the laravel-gdpr suite.

- `ImpactAssessment`: the processing, necessity and proportionality, and risks scored by likelihood × impact (1 to 3), with optional LINDDUN threats, measures and residual scores.
- DPO advice (Art. 35(2)) and approval with a scheduled review (Art. 35(11)). Editing an approved assessment sends it back to draft.
- `requiresPriorConsultation()`: a high residual risk means consulting the supervisory authority before processing (Art. 36(1)).
- `GdprDpia::candidates()` lists processing activities with special category or criminal data and no assessment (Art. 35(3)(b)). It links to laravel-gdpr-records.
- `gdpr:dpia [--json]` and `GdprDpia::export()`.

## [Unreleased]
