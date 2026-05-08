# Project Progress Reporting

## Goal
This folder stores progress reports in a dev-friendly format:
- Markdown for narrative reports
- PlantUML for timeline and blockers
- CSV for measurable data

## Structure
- `weekly/`: weekly reports (one file per ISO week)
- `monthly/`: monthly summary reports
- `diagrams/`: PlantUML charts
- `data/`: CSV source data for metrics
- `export/`: optional exported PDF files

## Update Workflow
1. Update `data/tasks-YYYY-Wxx.csv` and `data/bugs-YYYY-Wxx.csv` during the week.
2. Update `weekly/YYYY-Wxx.md` on Friday.
3. Adjust PUML charts in `diagrams/`.
4. Summarize into `monthly/YYYY-MM.md` at month end.

## Naming Convention
- Weekly: `YYYY-Wxx.md`
- Monthly: `YYYY-MM.md`
- Gantt: `gantt-YYYY-Wxx.puml`
- Blockers: `blockers-YYYY-Wxx.puml`

## Current Baseline
Current baseline report: `weekly/2026-W19.md`
