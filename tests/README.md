# Tests funcionales de LaCanchita

Suite end-to-end que levanta la app real contra una base MySQL/MariaDB de prueba
y verifica los flujos de los 5 perfiles (59 chequeos).

## Preparación
```bash
mysql -uroot -e "CREATE DATABASE lacanchita_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
mysql -uroot --default-character-set=utf8mb4 lacanchita_test < tests/schema_test.sql
for f in sql/*.sql; do mysql -uroot --default-character-set=utf8mb4 lacanchita_test < "$f"; done
mysql -uroot --default-character-set=utf8mb4 lacanchita_test < tests/seed_test.sql
```
> `schema_test.sql` fue **inferido del código** (no hay dump oficial del esquema en el repo);
> sirve además como referencia del modelo de datos.

## Correr
```bash
DB_NAME=lacanchita_test php -S 127.0.0.1:8088 -t . &   # server con la base de test
bash tests/suite.sh                                     # espera "TOTAL: PASS=62 FAIL=0"
```
Usuarios seed (password `test1234`): sa@test.com, dueno@test.com, enc@test.com,
emp@test.com, cliente@test.com, dueno2@test.com.

⚠️ La suite ESCRIBE en la base (reservas, pagos, usuarios): usar siempre una base
de prueba descartable, nunca la de desarrollo con datos reales.
