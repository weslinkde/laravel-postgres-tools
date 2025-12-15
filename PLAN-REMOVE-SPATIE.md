# Plan: Spatie-Dependencies entfernen

## Übersicht

**Aktuelle Spatie-Dependencies:**
1. `spatie/laravel-db-snapshots` (^2.6) - Haupt-Dependency
2. `spatie/laravel-package-tools` (^1.19.0) - ServiceProvider-Helper
3. `spatie/laravel-ray` (^1.39) - Dev-Dependency (kann bleiben)

**Transitive Dependencies von laravel-db-snapshots:**
- `spatie/db-dumper` - pg_dump/pg_restore Wrapper
- `spatie/temporary-directory` - Temp-Verzeichnis-Management

## Was muss übernommen werden

### 1. Aus `spatie/db-dumper` (~300 Zeilen relevant)

**Nur PostgreSQL-spezifischer Code benötigt:**

| Datei | Zeilen | Beschreibung |
|-------|--------|--------------|
| `DbDumper.php` | ~100 | Abstrakte Basis-Klasse |
| `Databases/PostgreSql.php` | ~160 | pg_dump Wrapper |
| `Exceptions/DumpFailed.php` | ~50 | Exception-Klasse |
| `Exceptions/CannotStartDump.php` | ~20 | Exception-Klasse |
| `Exceptions/CannotSetParameter.php` | ~20 | Exception-Klasse |

**NICHT benötigt:** MySQL, MariaDb, MongoDb, Sqlite, Compressors (wir verwenden pg_dump native compression)

### 2. Aus `spatie/laravel-db-snapshots` (~250 Zeilen relevant)

| Datei | Zeilen | Beschreibung |
|-------|--------|--------------|
| `Snapshot.php` | ~160 | Snapshot-Model (bereits überschrieben in PostgresSnapshot) |
| `SnapshotFactory.php` | ~100 | Snapshot-Erstellung |
| `SnapshotRepository.php` | ~40 | Repository (bereits überschrieben) |
| `Helpers/Format.php` | ~20 | humanReadableSize() |
| `Events/*.php` | ~60 | 6 Event-Klassen |

**NICHT benötigt:** Spatie's Commands (wir haben eigene), AsksForSnapshotName (wir haben eigene)

### 3. Aus `spatie/temporary-directory` (~100 Zeilen)

| Datei | Zeilen | Beschreibung |
|-------|--------|--------------|
| `TemporaryDirectory.php` | ~100 | Temp-Verzeichnis-Management |

### 4. Aus `spatie/laravel-package-tools`

**Alternative:** Standard Laravel ServiceProvider verwenden (kein Package nötig)

---

## Implementierungsplan

### Phase 1: Neue Klassen erstellen (kein Breaking Change)

**Task 1.1: Support-Klassen erstellen**
```
src/Support/
├── TemporaryDirectory.php      # Kopie von spatie/temporary-directory (vereinfacht)
└── Format.php                  # Kopie von Helpers/Format.php
```

**Task 1.2: Dumper-Klassen erstellen**
```
src/Dumper/
├── PostgresDumper.php          # Basis von spatie/db-dumper (nur PostgreSQL)
└── Exceptions/
    ├── DumpFailed.php
    ├── CannotStartDump.php
    └── CannotSetParameter.php
```

**Task 1.3: Snapshot-Klassen refactoren**
```
src/
├── Snapshot.php                # Kopie+Anpassung von Spatie's Snapshot.php
├── SnapshotFactory.php         # Kopie+Anpassung (verwendet unseren PostgresDumper)
├── SnapshotRepository.php      # Bereits vorhanden (PostgresSnapshotRepository)
└── Events/
    ├── CreatingSnapshot.php
    ├── CreatedSnapshot.php
    ├── LoadingSnapshot.php
    ├── LoadedSnapshot.php
    ├── DeletingSnapshot.php
    └── DeletedSnapshot.php
```

**Task 1.4: ServiceProvider umstellen**
- Von `Spatie\LaravelPackageTools\PackageServiceProvider` auf Standard `Illuminate\Support\ServiceProvider`
- Commands manuell registrieren

### Phase 2: Imports aktualisieren

**Task 2.1: Alle `use Spatie\...` Imports ersetzen**
- `src/Commands/CreateSnapshot.php`
- `src/Commands/LoadSnapshot.php`
- `src/Commands/DeleteSnapshot.php`
- `src/Commands/CloneDatabase.php`
- `src/DbDumperFactory.php` → integrieren in SnapshotFactory
- `src/PostgresSnapshot.php` → wird zu `src/Snapshot.php`
- `src/PostgresSnapshotRepository.php` → wird zu `src/SnapshotRepository.php`

### Phase 3: Composer.json bereinigen

**Task 3.1: Dependencies entfernen**
```json
{
  "require": {
    // ENTFERNEN:
    // "spatie/laravel-db-snapshots": "^2.6",
    // "spatie/laravel-package-tools": "^1.19.0"

    // BEHALTEN:
    "php": "^8.1",
    "illuminate/contracts": "^10.0||^11.0||^12.0",
    "laravel/prompts": "^0.3.5",
    "symfony/process": "^6.0||^7.0"  // Bereits transitiv vorhanden
  }
}
```

### Phase 4: Tests anpassen

**Task 4.1: Unit-Tests aktualisieren**
- Namespaces in Mocks anpassen
- Event-Klassen-Referenzen aktualisieren

**Task 4.2: Integration-Tests verifizieren**
- Alle 44 Tests müssen weiterhin bestehen

---

## Geschätzter Code-Umfang

| Komponente | Neue Zeilen | Entfernte Spatie-Deps |
|------------|-------------|----------------------|
| PostgresDumper | ~200 | spatie/db-dumper |
| TemporaryDirectory | ~80 | spatie/temporary-directory |
| Snapshot + Factory | ~200 | spatie/laravel-db-snapshots |
| Events | ~60 | spatie/laravel-db-snapshots |
| Format Helper | ~20 | spatie/laravel-db-snapshots |
| ServiceProvider | +30 | spatie/laravel-package-tools |
| **Gesamt** | **~590** | **4 Packages** |

---

## Vorteile nach Migration

1. **Keine externen Snapshot-Dependencies** - vollständige Kontrolle
2. **Kleinerer Footprint** - nur PostgreSQL-Code, kein MySQL/SQLite/MongoDB
3. **Einfachere Wartung** - keine Kompatibilitätsprobleme bei Spatie-Updates
4. **Bessere Performance** - weniger Abstraktion, direkter pg_dump/pg_restore Aufruf

## Risiken

1. **Zeitaufwand:** ~4-6 Stunden für saubere Implementation
2. **Events:** Nutzer die Spatie-Events abonniert haben, müssen auf neue Events umstellen
3. **Breaking Change:** Major Version Bump empfohlen (v1.0.0)

---

## Empfohlene Reihenfolge

1. ✅ Phase 1.1: TemporaryDirectory + Format Helper
2. ✅ Phase 1.2: PostgresDumper + Exceptions
3. ✅ Phase 1.3: Snapshot + SnapshotFactory + Events
4. ✅ Phase 1.4: ServiceProvider umstellen
5. ✅ Phase 2: Imports aktualisieren
6. ✅ Phase 3: composer.json bereinigen
7. ✅ Phase 4: Tests verifizieren
8. ✅ Release als v1.0.0
