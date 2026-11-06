/**
 * Sangfor HCI Compatibility Database Converter
 * Usage: node HCL-convert_csv_to_json.js
 *
 * Reads sangfor_site1_*.csv and sangfor_site2_*.csv files from the current directory
 * and produces an updated HCL-compatibility_data.json and HCL-compatibility_data.js.
 *
 * To add a new CSV export: place the file in the same directory and re-run.
 */

const fs = require('fs');
const path = require('path');

// ── Configuration ─────────────────────────────────────────────────────────────
const SITE1_FILES = fs.readdirSync('.').filter(f => f.startsWith('sangfor_site1') && f.endsWith('.csv'));
const SITE2_FILES = fs.readdirSync('.').filter(f => f.startsWith('sangfor_site2') && f.endsWith('.csv'));
const OUTPUT_JSON = 'HCL-compatibility_data.json';
const OUTPUT_JS   = 'HCL-compatibility_data.js';
// ─────────────────────────────────────────────────────────────────────────────

function parseCSVRow(line) {
  const result = [];
  let cur = '', inQuote = false;
  for (const c of line) {
    if (c === '"') { inQuote = !inQuote; }
    else if (c === ',' && !inQuote) { result.push(cur); cur = ''; }
    else { cur += c; }
  }
  result.push(cur);
  return result;
}

function parseCSV(filePath) {
  const text = fs.readFileSync(filePath, 'utf8').replace(/\r/g, '');
  const lines = text.split('\n').filter(l => l.trim());
  const headers = parseCSVRow(lines[0]).map(h => h.replace(/﻿/g, '').trim());
  return lines.slice(1).map(line => {
    const parts = parseCSVRow(line);
    return Object.fromEntries(headers.map((h, i) => [h, (parts[i] || '').trim()]));
  });
}

function normalizeStatus(s) {
  if (!s || s === '-') return 'Unknown';
  const low = s.toLowerCase();
  if (low === 'compatible' || s === '兼容' || s === '支持') return 'Compatible';
  if (low === 'not compatible' || s === '不支持') return 'Not Compatible';
  if (low === 'certificate') return 'Certificate';
  if (low === 'evaluated') return 'Evaluated';
  if (/^\d{4}-\d{2}-\d{2}/.test(s)) return 'Unknown';
  if (/[一-鿿]/.test(s)) return 'Unknown';
  return s;
}

// Load existing JSON to preserve manually-added entries
let existingEntries = [];
let nextId = 1;
if (fs.existsSync(OUTPUT_JSON)) {
  try {
    const existing = JSON.parse(fs.readFileSync(OUTPUT_JSON, 'utf8'));
    existingEntries = (existing.entries || []).filter(e => e.manually_added);
    const maxId = existing.entries.reduce((m, e) => Math.max(m, e.id || 0), 0);
    nextId = maxId + 1;
  } catch (e) {
    console.warn('Could not read existing JSON, starting fresh.');
  }
}

const csvEntries = [];

// Parse Site1 files
for (const file of SITE1_FILES) {
  console.log('Reading', file);
  const rows = parseCSV(file);
  for (const r of rows) {
    csvEntries.push({
      id: nextId++,
      platform: r.Platform || 'HCI',
      category: r.Category || '',
      model: r.Model || '',
      manufacturer: r.Manufacturer || '',
      architecture: r.Architecture || '',
      status: normalizeStatus(r.Status),
      url: r.Compatible_Version_URL || '',
      recommended_config: '',
      remarks: '',
      source: r.Source || 'Site1',
      update_time: r.Update_Time || '',
      manually_added: false
    });
  }
}

// Parse Site2 files
for (const file of SITE2_FILES) {
  console.log('Reading', file);
  const rows = parseCSV(file);
  for (const r of rows) {
    csvEntries.push({
      id: nextId++,
      platform: r.Platform || 'HCI',
      category: r.Hardware_Type || '',
      model: r.Model || '',
      manufacturer: r.Manufacturer || '',
      architecture: r.Architecture || '',
      status: normalizeStatus(r.Status),
      url: '',
      recommended_config: r.Recommended_Config || '',
      remarks: r.Remarks || '',
      source: r.Source || 'Site2',
      update_time: r.Update_Time || '',
      manually_added: false
    });
  }
}

// Merge: CSV entries first, then preserved manual entries
const allEntries = [...csvEntries, ...existingEntries];

const categories = [...new Set(allEntries.map(e => e.category))].filter(Boolean).sort();
const manufacturers = [...new Set(allEntries.map(e => e.manufacturer))].filter(Boolean).sort();

const db = {
  metadata: {
    last_updated: new Date().toISOString().split('T')[0],
    total_entries: allEntries.length,
    version: '1.0',
    sources: ['Site1', 'Site2'],
    categories,
    manufacturers
  },
  entries: allEntries
};

const jsonString = JSON.stringify(db, null, 2);
fs.writeFileSync(OUTPUT_JSON, jsonString);

// Also write HCL-compatibility_data.js for direct file:// loading (no web server needed)
fs.writeFileSync(OUTPUT_JS, 'window.COMPAT_DB = ' + jsonString + ';');

console.log(`\nDone! ${allEntries.length} total entries written to ${OUTPUT_JSON}`);
console.log(`  CSV entries   : ${csvEntries.length}`);
console.log(`  Manual entries: ${existingEntries.length}`);
console.log(`  Categories    : ${categories.join(', ')}`);
console.log(`  JSON size     : ${Math.round(fs.statSync(OUTPUT_JSON).size / 1024)} KB`);
