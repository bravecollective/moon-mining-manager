---
name: Bug report
about: Something in the app does not work
title: ''
labels: bug
assignees: ''
---

## Summary

<!-- One or two sentences. What broke, and where. -->

## Current behavior

<!-- Quote the exact error, the wrong figure, or the empty page. -->

## Expected behavior

## Steps to reproduce

1.
2.
3.

## Environment

- Version or commit SHA:
- [ ] I imported the EVE static data with `php artisan eve:import-static-data`

<!-- The static data question matters. The moon list, extractions, taxes and
     reports pages join `invTypes` and `mapRegions` for the names they show.
     Without the import they fail in ways that look exactly like app bugs. -->

## Logs

<!-- Rows from the `logs` table, plus storage/logs/ if there is a stack trace.
     Remove tokens and secrets. -->
