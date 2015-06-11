# PHP-SQL-Parser Manual #

## How to integrate php-sql-parse into your application ##
The parser comes with multiple PHP files, which are downloadable from http://code.google.com/p/php-sql-parser/downloads/list (stable version).  It does not require any PECL packages. The latest development version is accessible on http://code.google.com/p/php-sql-parser/source/browse/trunk.

---

  1. Download the SQL parser from: http://code.google.com/p/php-sql-parser/downloads/list and unzip it into your include directory.
  1. add **require\_once**('php-sql-parser.php') to your application
  1. Use the parser:
```
$parser = new PHPSQLParser();
$parsed = $parser->parse($sql);
print_r($parsed);
```
  1. it is also possible to generate keyword positions during the parser step
  1. for every base\_expr entry the parser stores the position within the original SQL string
```
$parser = new PHPSQLParser();
$parsed = $parser->parse($sql, true);
print_r($parsed);
```


## Trying the examples ##
The best way to see how to use the parser is to look at the extensive examples, which you can get here:
  1. Download the SQL parser from: http://code.google.com/p/php-sql-parser/downloads/list and unzip it into your include directory.
  1. There is a file example.php, that contains a lot of examples. More examples you can find within the /test folder.
  1. Execute the example:
```
php example.php
```

## Using the parser ##

### There are two ways in which you can parse statements ###

  1. Use the **constructor**
```
/* The constructor simply calls the parse() method on the provided SQL for convenience.*/
$parser = new PHPSQLParser('select 1');
print_r($parser->parsed);
```
  1. Use the **parse**() method
```
$parser = new PHPSQLParser();
print_r($parser->parse('select 2')); /* this is okay, the tree is saved in the _parsed_ property.

/* get the tree for the last parsed statement */
$save = $parser->parsed;
```

There are no other public functions.


## Using the creator ##

### There are two ways in which you can create statements from parser output ###

  1. Use the **constructor**
```
/* The constructor simply calls the create() method on the provided parser tree output for convenience. */
$parser = new PHPSQLParser('select 1');
$creator = new PHPSQLCreator($parser->parsed);
echo $creator->created;
```
  1. Use the **create**() method
```
$parser = new PHPSQLParser('select 2');
$creator = new PHPSQLCreator();
echo $creator->create($parser->parsed); /* this is okay, the SQL is saved in the _created_ property. */

/* get the SQL statement for the last parsed statement */
$save = $creator->created;
```

There are no other public functions.


## Parse tree overview ##
The parsed representation returned by php-sql-parser is an associative array of important SQL sections and the information about the clauses in each of those sections.

Because this is easier to visualize, I'll provide a simple example.  As I said, the parser splits up the query into sections. Later the manual will describe what sections are available each of the supported SQL statement types.

In the example the given query has three sections: **SELECT**,**FROM**,**WHERE**.  You will see each of these sections in the parser output.  Each of those sections contain _items_.  Each _item_ represents a keyword, a literal value, a subquery, an expression or a column reference.

In the following example, the **SELECT** section contains one _item_ which is a column reference (colref).  The FROM clause contains only one table.  You'll notice that it still says 'JOIN'.  Don't be confused by this.  Every table item is a join, but it may not have any join critera.  Finally, the where clause consists of three items, a colref, an operator and a literal value (const).

There is a ComplexExample which features almost all of the available SELECT syntax.

### simple example ###

---

```
<?php
  require_once('php-sql-parser.php');
  $parser=new PHPSQLParser('
SELECT a 
  from some_table an_alias
 WHERE d > 5;
', true);

  print_r($parser->parsed);  
```

**Output**

---

```
Array
(
    [SELECT] => Array
        (
            [0] => Array
                (
                    [expr_type] => colref
                    [alias] => 
                    [base_expr] => a
                    [sub_tree] => 
                    [position] => 8
                )

        )

    [FROM] => Array
        (
            [0] => Array
                (
                    [expr_type] => table
                    [table] => some_table
                    [alias] => Array
                        (
                            [as] => 
                            [name] => an_alias
                            [base_expr] => an_alias
                            [position] => 29
                        )

                    [join_type] => JOIN
                    [ref_type] => 
                    [ref_clause] => 
                    [base_expr] => some_table an_alias
                    [sub_tree] => 
                    [position] => 18
                )

        )

    [WHERE] => Array
        (
            [0] => Array
                (
                    [expr_type] => colref
                    [base_expr] => d
                    [sub_tree] => 
                    [position] => 45
                )

            [1] => Array
                (
                    [expr_type] => operator
                    [base_expr] => >
                    [sub_tree] => 
                    [position] => 47
                )

            [2] => Array
                (
                    [expr_type] => const
                    [base_expr] => 5
                    [sub_tree] => 
                    [position] => 49
                )

        )

)
```