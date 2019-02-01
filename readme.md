# Twilight dumper

---

## Usage:

### Connect and generate:

`require 'dumper.class.php';`
`$dumper = new Dumper($username, $password, $database);`
`$dumper->generate();`

### Save to .sql file:

`$dumper->save();`

### Load .sql:

`$dumper->upload( $filename );`

---

## Additional functions:

### Raw SQL:

`$dumper->sql`

### Count rows:

`$dumper->rows_summary`

### Error status:

`$dumper->error`