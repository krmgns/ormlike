Actually, I'm just trying to practice on the ORM concept. But it works, trust me!

**USAGE**

1- Set your database connection infos here: `ORMLikeDatabaseAbstract::$_cfg`.<br>
2- Be sure that PHP version >= 5.3

- Simple

```php
class Books extends ORMLike {
    protected $_table = 'books';
    protected $_primaryKey = 'id';
}

$booksObject = new Books();

/* Find one and print title */
$book = $booksObject->find(1);
print $book->title;
// or
print $book->getTitle();

/* Insert a book */
$booksObject->title = 'The PHP';
$booksObject->price = 11.59;
// or
$booksObject->setTitle('The PHP');
$booksObject->setPrice(11.59);
// save it!
$booksObject->save();

/* Update a book */
// set target row id (here our primary key is "id")
$booksObject->id = 1;
$booksObject->title = 'The PHP';
$booksObject->price = 11.59;
// or
$booksObject->setId(1);
$booksObject->setTitle('The PHP');
$booksObject->setPrice(11.59);
// save it!
$booksObject->save();

/* Remove a book */
$booksObject->remove(1);
// or
$booksObject->remove(array(1,2,3));
```

- Set/Get properties literally (which is already set)

```php
class Books extends ORMLike {
    protected $_table = 'books';
    protected $_primaryKey = 'id';
    
    public $id;
    public $title;
    public $price;
}

$booksObject = new Books();
/* Set */
$booksObject->id = 1;
$booksObject->title = 'PHP in Action';
$booksObject->price = 14.55;
// or
$booksObject->setId(1);
$booksObject->setTitle('PHP in Action');
$booksObject->setPrice(14.55);

/* Get */
print $booksObject->getId(1);   // 1
print $booksObject->getTitle(); // PHP in Action
print $booksObject->getPrice(); // 14.55
// or
print $booksObject->id;    // 1
print $booksObject->title; // PHP in Action
print $booksObject->price; // 14.55
```

- Set/Get fantastic field names (just a suggestion)

```php
class Books extends ORMLike {
    protected $_table = 'books';
    protected $_primaryKey = 'id';
    
    // Assuming the field name is "last_update_date_unix_timestamp"
    public function setLastUpdateUTS($ts) {
        $this->last_update_date_unix_timestamp = $ts;
    }
    public function getLastUpdateUTS() {
        return $this->last_update_date_unix_timestamp;
    }
}

$booksObject = new Books();
// You can prefer this way
$booksObject->setLastUpdateUTS(time());
print $booksObject->getLastUpdateUTS();
// This also works, your choice
$booksObject->last_update_date_unix_timestamp = time();
print $booksObject->last_update_date_unix_timestamp;
```

- Retrieve data with `findAll`

```php
// Assuming "id" was already set as primary key.
// Without external params
$books = $booksObject->findAll('WHERE id IN(1,2,3)');
// With external params (here we kick out WHERE)
$books = $booksObject->findAll('id IN(?)', array(1,2,3));
$books = $booksObject->findAll('title LIKE ?', "PHP's%"); // Yes, it's safe

foreach ($books as $book) {
    print $book->getTitle();
}
```

- Check wheter data is empty or not

```php
$book = $booksObject->find(1);
if ($book->isFound()) {
    print $book->getTitle();
} else {
    print 'Book not found!';
}
// and
print 'Found books: '. $book->count();

// Note: "isFound" is useless for "findAll", use "count" instead.
$books = $booksObject->findAll('WHERE id IN(1,2,3)');
if ($books->count()) {
    print 'Found books: '. $book->count();
}
```

- Convert entity to array

```php
$book = $booksObject->find(1);
$bookArray = $book->toArray();
// or
$books = $booksObject->findAll('WHERE id IN(1,2,3)');
$booksArray = $books->toArray();
```

- Loops (easy!)

```php
// For "findAll" (or for "find" as well)
$books = $booksObject->findAll('WHERE id IN(1,2,3)');
foreach ($books as $book) {
    print $book->getTitle();
}
```

**EXTRA**

- We have a database adapter

```php
$db = ORMLikeDatabase::init();

// Output: title = 'PHP\'s Power'
$db->prepare('title = ?', "PHP's Power");
$db->prepare('title = %s', "PHP's Power");
$db->prepare('title = :title', array(':title' => "PHP's Power"));

// Output: mysqli_result Object(...)
$db->query("SELECT * FROM books");
$db->query("INSERT INTO books VALUES('', 'Test', 1.3)");
// Output: (int) n
print $db->numRows;
print $db->insertId;

// Output: stdClass Object(...)
$db->get("SELECT * FROM books");
$db->get("SELECT * FROM books WHERE id = %d", 1);
$db->get("SELECT * FROM books WHERE id = ?", array($_GET['id']));
$db->get("SELECT * FROM books WHERE title LIKE %s OR LIKE ?", array('Test"s', 'Foo'));
// Output: Array(...)
$db->get("SELECT * FROM books", null, ORMLikeDatabase::FETCH_ASSOC);
// Output: (int) n
$db->numRows;

// Output: Array(stdClass Object(...) ...)
$db->getAll("SELECT * FROM books");
$db->getAll("SELECT * FROM books WHERE id IN(?)", array(array(1,2,3)));
$db->getAll("SELECT * FROM books WHERE id = %d OR id = %d", array(1,2));
// Output: Array(Array(...) ...)
$db->getAll("SELECT * FROM books", null, ORMLikeDatabase::FETCH_ASSOC);
// Output: (int) n
$db->numRows;

/* CUD opts */
$db->insert('books', array('title' => "Test's", 'price' => 1.35));
print $db->insertId;

$db->update('books', array('title' => "Test's", 'price' => 1.29), 'id = 1');
$db->update('books', array('title' => "Test's", 'price' => 1.29), 'id = ?', 1);
print $db->affectedRows;

$db->delete('books', 'id = 1');
$db->delete('books', 'id = ?', 1);
print $db->affectedRows;

/* Profiler properties */
$db->queryCount;
$db->timerStart;
$db->timerStop;
$db->timerProcess;
$db->timerProcessTotal;
```