#########################
Using CodeIgniter's Model
#########################

.. contents::
    :local:
    :depth: 3

Models
******

The CodeIgniter's Model provides convenience features and additional functionality
that people commonly use to make working with a **single table** in your database more convenient.

It comes out of the box with helper
methods for much of the standard ways you would need to interact with a database table, including finding records,
updating records, deleting records, and more.

.. _accessing-models:

Accessing Models
****************

Models are typically stored in the **app/Models** directory. They should have a namespace that matches their
location within the directory, like ``namespace App\Models``.

You can access models within your classes by creating a new instance or using the :php:func:`model()` helper function.

.. literalinclude:: model/001.php

The ``model()`` uses ``Factories::models()`` internally.
See :ref:`factories-loading-class` for details on the first parameter.

CodeIgniter's Model
*******************

CodeIgniter does provide a model class that has a few nice features, including:

- automatic database connection
- basic CRUD methods
- :ref:`in-model validation <in-model-validation>`
- :ref:`automatic pagination <paginating-with-models>`
- and more

This class provides a solid base from which to build your own models, allowing you to
rapidly build out your application's model layer.

Creating Your Model
*******************

To take advantage of CodeIgniter's model, you would simply create a new model class
that extends ``CodeIgniter\Model``:

.. literalinclude:: model/002.php

This empty class provides convenient access to the database connection, the Query Builder,
and a number of additional convenience methods.

initialize()
============

Should you need additional setup in your model you may extend the ``initialize()`` method
which will be run immediately after the Model's constructor. This allows you to perform
extra steps without repeating the constructor parameters, for example extending other models:

.. literalinclude:: model/003.php

Connecting to the Database
==========================

When the class is first instantiated, if no database connection instance is passed to the constructor,
and if you don't set the ``$DBGroup`` property on your model class,
it will automatically connect to the default database group, as set in the database configuration.

You can
modify which group is used on a per-model basis by adding the ``$DBGroup`` property to your class.
This ensures that within the model any references to ``$this->db`` are made through the appropriate
connection.

.. literalinclude:: model/004.php

You would replace "group_name" with the name of a defined database group from the database
configuration file.

Configuring Your Model
======================

The model class has some configuration options that can be set to allow the class' methods
to work seamlessly for you. The first two are used by all of the CRUD methods to determine
what table to use and how we can find the required records:

.. literalinclude:: model/005.php

$table
------

Specifies the database table that this model primarily works with. This only applies to the
built-in CRUD methods. You are not restricted to using only this table in your own
queries.

$primaryKey
-----------

This is the name of the column that uniquely identifies the records in this table. This
does not necessarily have to match the primary key that is specified in the database, but
is used with methods like ``find()`` to know what column to match the specified value to.

.. note:: All Models must have a primaryKey specified to allow all of the features to work
    as expected.

$useAutoIncrement
-----------------

Specifies if the table uses an auto-increment feature for `$primaryKey`_. If set to ``false``
then you are responsible for providing primary key value for every record in the table. This
feature may be handy when we want to implement 1:1 relation or use UUIDs for our model. The
default value is ``true``.

.. note:: If you set `$useAutoIncrement`_ to ``false``, then make sure to set your primary
    key in the database to ``unique``. This way you will make sure that all of Model's features
    will still work the same as before.

$returnType
-----------

The Model's **find*()** methods will take a step of work away from you and automatically
return the resulting data, instead of the Result object.

This setting allows you to define the type of data that is returned. Valid values
are '**array**' (the default), '**object**', or the **fully qualified name of a class**
that can be used with the Result object's ``getCustomResultObject()`` method.

Using the special ``::class`` constant of the class will allow most IDEs to
auto-complete the name and allow functions like refactoring to better understand
your code.

.. _model-use-soft-deletes:

$useSoftDeletes
---------------

If true, then any ``delete()`` method calls will set ``deleted_at`` in the database, instead of
actually deleting the row. This can preserve data when it might be referenced elsewhere, or
can maintain a "recycle bin" of objects that can be restored, or even simply preserve it as
part of a security trail. If true, the **find*()** methods will only return non-deleted rows, unless
the ``withDeleted()`` method is called prior to calling the **find*()** method.

This requires either a DATETIME or INTEGER field in the database as per the model's
`$dateFormat`_ setting. The default field name is ``deleted_at`` however this name can be
configured to any name of your choice by using `$deletedField`_ property.

.. important:: The ``deleted_at`` field in the database must be nullable.

.. _model-allowed-fields:

$allowedFields
--------------

This array should be updated with the field names that can be set during ``save()``, ``insert()``, or
``update()`` methods. Any field names other than these will be discarded. This helps to protect
against just taking input from a form and throwing it all at the model, resulting in
potential mass assignment vulnerabilities.

.. note:: The `$primaryKey`_ field should never be an allowed field.

$allowEmptyInserts
------------------

.. versionadded:: 4.3.0

Whether to allow inserting empty data. The default value is ``false``, meaning
that if you try to insert empty data, ``DataException`` with
"There is no data to insert." will raise.

You may also change this setting with the :ref:`model-allow-empty-inserts` method.

.. _model-update-only-changed:

$updateOnlyChanged
------------------

.. versionadded:: 4.5.0

Whether to update :doc:`Entity <./entities>`'s only changed fields. The default
value is ``true``, meaning that only changed field data is used when updating to
the database. So if you try to update an Entity without changes, ``DataException``
with "There is no data to update." will raise.

Setting this property to ``false`` will ensure that all allowed fields of an Entity
are submitted to the database and updated at any time.

$casts
------

.. versionadded:: 4.5.0

This allows you to convert data retrieved from a database into the appropriate
PHP type.
This option should be an array where the key is the name of the field, and the
value is the data type. See :ref:`model-field-casting` for details.

Dates
-----

$useTimestamps
^^^^^^^^^^^^^^

This boolean value determines whether the current date is automatically added to all inserts
and updates. If ``true``, will set the current time in the format specified by `$dateFormat`_. This
requires that the table have columns named **created_at**, **updated_at** and **deleted_at** in the appropriate
data type. See also `$createdField`_, `$updatedField`_, and `$deletedField`_.

$dateFormat
^^^^^^^^^^^

This value works with `$useTimestamps`_ and `$useSoftDeletes`_ to ensure that the correct type of
date value gets inserted into the database. By default, this creates DATETIME values, but
valid options are: ``'datetime'``, ``'date'``, or ``'int'`` (a UNIX timestamp). Using `$useSoftDeletes`_ or
`$useTimestamps`_ with an invalid or missing `$dateFormat`_ will cause an exception.

$createdField
^^^^^^^^^^^^^

Specifies which database field to use for data record create timestamp.
Set to an empty string (``''``) to avoid updating it (even if `$useTimestamps`_ is enabled).

$updatedField
^^^^^^^^^^^^^

Specifies which database field should use for keep data record update timestamp.
Set to an empty string (``''``) to avoid updating it (even `$useTimestamps`_ is enabled).

$deletedField
^^^^^^^^^^^^^

Specifies which database field to use for soft deletions. See :ref:`model-use-soft-deletes`.

Validation
----------

$validationRules
^^^^^^^^^^^^^^^^

Contains either an array of validation rules as described in :ref:`validation-array`
or a string containing the name of a validation group, as described in the same section.
See also :ref:`model-setting-validation-rules`.

$validationMessages
^^^^^^^^^^^^^^^^^^^

Contains an array of custom error messages that should be used during validation, as
described in :ref:`validation-custom-errors`. See also :ref:`model-setting-validation-rules`.

$skipValidation
^^^^^^^^^^^^^^^

Whether validation should be skipped during all **inserts** and **updates**. The default
value is ``false``, meaning that data will always attempt to be validated. This is
primarily used by the ``skipValidation()`` method, but may be changed to ``true`` so
this model will never validate.

.. _clean-validation-rules:

$cleanValidationRules
^^^^^^^^^^^^^^^^^^^^^

Whether validation rules should be removed that do not exist in the passed data.
This is used in **updates**.
The default value is ``true``, meaning that validation rules for the fields
that are not present in the passed data will be (temporarily) removed before the validation.
This is to avoid validation errors when updating only some fields.

You can also change the value by the ``cleanRules()`` method.

.. note:: Prior to v4.2.7, ``$cleanValidationRules`` did not work due to a bug.

Callbacks
---------

$allowCallbacks
^^^^^^^^^^^^^^^

Whether the callbacks defined below should be used. See :ref:`model-events`.

$beforeInsert
^^^^^^^^^^^^^
$afterInsert
^^^^^^^^^^^^
$beforeUpdate
^^^^^^^^^^^^^
$afterUpdate
^^^^^^^^^^^^^
$beforeFind
^^^^^^^^^^^
$afterFind
^^^^^^^^^^
$beforeDelete
^^^^^^^^^^^^^
$afterDelete
^^^^^^^^^^^^
$beforeInsertBatch
^^^^^^^^^^^^^^^^^^
$afterInsertBatch
^^^^^^^^^^^^^^^^^
$beforeUpdateBatch
^^^^^^^^^^^^^^^^^^
$afterUpdateBatch
^^^^^^^^^^^^^^^^^

These arrays allow you to specify callback methods that will be run on the data at the
time specified in the property name. See :ref:`model-events`.

.. _model-field-casting:

Model Field Casting
*******************

.. versionadded:: 4.5.0

When retrieving data from a database, data of integer type may be converted to
string type in PHP. You may also want to convert date/time data into a Time
object in PHP.

Model Field Casting allows you to convert data retrieved from a database into
the appropriate PHP type.

.. important::
    If you use this feature with the :doc:`Entity <./entities>`, do not use
    :ref:`Entity Property Casting <entities-property-casting>`. Using both casting
    at the same time does not work.

    Entity Property Casting works at (1)(4), but this casting works at (2)(3)::

        [App Code] --- (1) --> [Entity] --- (2) --> [Database]
        [App Code] <-- (4) --- [Entity] <-- (3) --- [Database]

    When using this casting, Entity will have correct typed PHP values in the
    attributes. This behavior is completely different from the previous behavior.
    Do not expect the attributes hold raw data from database.

Defining Data Types
===================

The ``$casts`` property sets its definition. This option should be an array
where the key is the name of the field, and the value is the data type:

.. literalinclude:: model/057.php

Data Types
==========

The following types are provided by default. Add a question mark at the beginning
of type to mark the field as nullable, i.e., ``?int``, ``?datetime``.

+---------------+----------------+---------------------------+
| Type          | PHP Value Type | DB Column Type            |
+===============+================+===========================+
|``int``        | int            | int type                  |
+---------------+----------------+---------------------------+
|``float``      | float          | float (numeric) type      |
+---------------+----------------+---------------------------+
|``bool``       | bool           | bool/int/string type      |
+---------------+----------------+---------------------------+
|``int-bool``   | bool           | int type (1 or 0)         |
+---------------+----------------+---------------------------+
|``array``      | array          | string type (serialized)  |
+---------------+----------------+---------------------------+
|``csv``        | array          | string type (CSV)         |
+---------------+----------------+---------------------------+
|``json``       | stdClass       | json/string type          |
+---------------+----------------+---------------------------+
|``json-array`` | array          | json/string type          |
+---------------+----------------+---------------------------+
|``datetime``   | Time           | datetime type             |
+---------------+----------------+---------------------------+
|``timestamp``  | Time           | int type (UNIX timestamp) |
+---------------+----------------+---------------------------+
|``uri``        | URI            | string type               |
+---------------+----------------+---------------------------+

csv
---

Casting as ``csv`` uses PHP's internal ``implode()`` and ``explode()`` functions
and assumes all values are string-safe and free of commas. For more complex data
casts try ``array`` or ``json``.

datetime
--------

You can pass a parameter like ``datetime[ms]`` for date/time with milliseconds,
or ``datetime[us]`` for date/time with microseconds.

The datetime format is set in the ``dateFormat`` array of the
:ref:`database configuration <database-config-explanation-of-values>` in the
**app/Config/Database.php** file.

.. note::
    When you set ``ms`` or ``us`` as a parameter, **Model** takes care of second's
    fractional part of the Time. But **Query Builder** does not. So you still need
    to use the ``format()`` method when you pass the Time to Query Builder's methods
    like ``where()``:

    .. literalinclude:: model/063.php
        :lines: 2-

.. note:: Prior to v4.6.0, you cannot use ``ms`` or ``us`` as a parameter.
    Because the second's fractional part of Time was lost due to bugs.

timestamp
---------

The timezone of the ``Time`` instance created will be the default timezone
(app's timezone), not UTC.

Custom Casting
==============

You can define your own conversion types.

Creating Custom Handlers
------------------------

At first you need to create a handler class for your type.
Let's say the class will be located in the **app/Models/Cast** directory:

.. literalinclude:: model/058.php

If you don't need to change values when getting or setting a value. Then just
don't implement the appropriate method:

.. literalinclude:: model/060.php

Registering Custom Handlers
---------------------------

Now you need to register it:

.. literalinclude:: model/059.php

Parameters
----------

In some cases, one type is not enough. In this situation, you can use additional
parameters. Additional parameters are indicated in square brackets and listed
with a comma like ``type[param1, param2]``.

.. literalinclude:: model/061.php

.. literalinclude:: model/062.php

.. note:: If the casting type is marked as nullable like ``?bool`` and the passed
    value is not null, then the parameter with the value ``nullable`` will be
    passed to the casting type handler. If casting type has predefined parameters,
    then ``nullable`` will be added to the end of the list.

Working with Data
*****************

Finding Data
============

Several functions are provided for doing basic CRUD work on your tables, including ``find()``,
``insert()``, ``update()``, ``delete()`` and more.

find()
------

Returns a single row where the primary key matches the value passed in as the first parameter:

.. literalinclude:: model/006.php

The value is returned in the format specified in `$returnType`_.

You can specify more than one row to return by passing an array of primaryKey values instead
of just one:

.. literalinclude:: model/007.php

.. note:: If no parameters are passed in, ``find()`` will return all rows in that model's table,
    effectively acting like ``findAll()``, though less explicit.

findColumn()
------------

Returns null or an indexed array of column values:

.. literalinclude:: model/008.php

``$columnName`` should be a name of single column else you will get the ``DataException``.

findAll()
---------

Returns all results:

.. literalinclude:: model/009.php

This query may be modified by interjecting Query Builder commands as needed prior to calling this method:

.. literalinclude:: model/010.php

You can pass in a limit and offset values as the first and second
parameters, respectively:

.. literalinclude:: model/011.php

first()
-------

Returns the first row in the result set. This is best used in combination with the query builder.

.. literalinclude:: model/012.php

withDeleted()
-------------

If `$useSoftDeletes`_ is true, then the **find*()** methods will not return any rows where ``deleted_at IS NOT NULL``.
To temporarily override this, you can use the ``withDeleted()`` method prior to calling the **find*()** method.

.. literalinclude:: model/013.php

onlyDeleted()
-------------

Whereas ``withDeleted()`` will return both deleted and not-deleted rows, this method modifies
the next **find*()** methods to return only soft deleted rows:

.. literalinclude:: model/014.php

Saving Data
===========

insert()
--------

The first parameter is an associative array of data to create a new row of data in the database.
If an object is passed instead of an array, it will attempt to convert it to an array.

The array's keys must match the name of the columns in the `$table`_, while the array's values are the values to save for that key.

The optional second parameter is of type boolean, and if it is set to false, the method will return a boolean value,
which indicates the success or failure of the query.

You can retrieve the last inserted row's primary key using the ``getInsertID()`` method.

.. literalinclude:: model/015.php

.. _model-allow-empty-inserts:

allowEmptyInserts()
-------------------

.. versionadded:: 4.3.0

You can use ``allowEmptyInserts()`` method to insert empty data. The Model throws an exception when you try to insert empty data by default. But if you call this method, the check will no longer be performed.

.. literalinclude:: model/056.php

You may also change this setting with the `$allowEmptyInserts`_ property.

You can enable the check again by calling ``allowEmptyInserts(false)``.

update()
--------

Updates an existing record in the database. The first parameter is the `$primaryKey`_ of the record to update.
An associative array of data is passed into this method as the second parameter. The array's keys must match the name
of the columns in a `$table`_, while the array's values are the values to save for that key:

.. literalinclude:: model/016.php

.. important:: Since v4.3.0, this method raises a ``DatabaseException``
    if it generates an SQL statement without a WHERE clause.
    In previous versions, if it is called without `$primaryKey`_ specified and
    an SQL statement was generated without a WHERE clause, the query would still
    execute and all records in the table would be updated.

Multiple records may be updated with a single call by passing an array of primary keys as the first parameter:

.. literalinclude:: model/017.php

When you need a more flexible solution, you can leave the parameters empty and it functions like the Query Builder's
update command, with the added benefit of validation, events, etc:

.. literalinclude:: model/018.php

.. _model-save:

save()
------

This is a wrapper around the ``insert()`` and ``update()`` methods that handle inserting or updating the record
automatically, based on whether it finds an array key matching the **primary key** value:

.. literalinclude:: model/019.php

The save method also can make working with custom class result objects much simpler by recognizing a non-simple
object and grabbing its public and protected values into an array, which is then passed to the appropriate
insert or update method. This allows you to work with Entity classes in a very clean way. Entity classes are
simple classes that represent a single instance of an object type, like a user, a blog post, a job, etc. This
class is responsible for maintaining the business logic surrounding the object itself, like formatting
elements in a certain way, etc. They shouldn't have any idea about how they are saved to the database. At their
simplest, they might look like this:

.. literalinclude:: model/020.php

A very simple model to work with this might look like:

.. literalinclude:: model/021.php

This model works with data from the ``jobs`` table, and returns all results as an instance of ``App\Entities\Job``.
When you need to persist that record to the database, you will need to either write custom methods, or use the
model's ``save()`` method to inspect the class, grab any public and private properties, and save them to the database:

.. literalinclude:: model/022.php

.. note:: If you find yourself working with Entities a lot, CodeIgniter provides a built-in :doc:`Entity class </models/entities>`
    that provides several handy features that make developing Entities simpler.

.. _model-saving-dates:

Saving Dates
------------

.. versionadded:: 4.5.0

When saving data, if you pass :doc:`Time <../libraries/time>` instances, they are
converted to strings with the format defined in ``dateFormat['datetime']`` and
``dateFormat['date']`` in the
:ref:`database configuration <database-config-explanation-of-values>`.

.. note:: Prior to v4.5.0, the date/time formats were hard coded as ``Y-m-d H:i:s``
    and ``Y-m-d`` in the Model class.

Deleting Data
=============

delete()
--------

Takes a primary key value as the first parameter and deletes the matching record from the model's table:

.. literalinclude:: model/023.php

If the model's `$useSoftDeletes`_ value is true, this will update the row to set ``deleted_at`` to the current
date and time. You can force a permanent delete by setting the second parameter as true.

An array of primary keys can be passed in as the first parameter to delete multiple records at once:

.. literalinclude:: model/024.php

If no parameters are passed in, will act like the Query Builder's delete method, requiring a where call
previously:

.. literalinclude:: model/025.php

purgeDeleted()
--------------

Cleans out the database table by permanently removing all rows that have 'deleted_at IS NOT NULL'.

.. literalinclude:: model/026.php

.. _in-model-validation:

In-Model Validation
===================

.. warning:: In-Model validation is performed just before data is stored in the
    database. Prior to that point, the data has not yet been validated. Processing
    user-input data prior to validation may introduce vulnerabilities.

Validating Data
---------------

The Model class provides a way to automatically have all data validated
prior to saving to the database with the ``insert()``, ``update()``, or ``save()`` methods.

.. important:: When you update data, by default, the validation in the model class only
    validates provided fields. This is to avoid validation errors when updating only some fields.

    However, this means that not all validation rules you set will be checked
    during updates. Thus, incomplete data may pass the validation.

    For example, ``required*`` rules or ``is_unique`` rule that require the
    values of other fields may not work as expected.

    To avoid such glitches, this behavior can be changed by configuration. See
    :ref:`clean-validation-rules` for details.

.. _model-setting-validation-rules:

Setting Validation Rules
------------------------

The first step is to fill out the `$validationRules`_ class property with the
fields and rules that should be applied.

.. note:: You can see the list of built-in Validation rules in :ref:`validation-available-rules`.

If you have custom error message that you want to use, place them in the `$validationMessages`_ array:

.. literalinclude:: model/027.php

If you'd rather organize your rules and error messages within the
:ref:`Validation Config File <saving-validation-rules-to-config-file>`, you can
do that and simply set `$validationRules`_ to the name of the validation rule
group you created:

.. literalinclude:: model/034.php

The other way to set the validation rules to fields by functions,

.. php:namespace:: CodeIgniter

.. php:class:: Model

.. php:method:: setValidationRule($field, $fieldRules)

    :param  string  $field:
    :param  array   $fieldRules:

    This function will set the field validation rules.

    Usage example:

    .. literalinclude:: model/028.php

.. php:method:: setValidationRules($validationRules)

    :param  array   $validationRules:

    This function will set the validation rules.

    Usage example:

    .. literalinclude:: model/029.php

The other way to set the validation message to fields by functions,

.. php:method:: setValidationMessage($field, $fieldMessages)

    :param  string  $field:
    :param  array   $fieldMessages:

    This function will set the field wise error messages.

    Usage example:

    .. literalinclude:: model/030.php

.. php:method:: setValidationMessages($fieldMessages)

    :param  array   $fieldMessages:

    This function will set the field messages.

    Usage example:

    .. literalinclude:: model/031.php

Getting Validation Result
-------------------------

Now, whenever you call the ``insert()``, ``update()``, or ``save()`` methods, the data will be validated. If it fails,
the model will return boolean **false**.

.. _model-getting-validation-errors:

Getting Validation Errors
-------------------------

You can use the ``errors()`` method to retrieve the validation errors:

.. literalinclude:: model/032.php

This returns an array with the field names and their associated errors that can be used to either show all of the
errors at the top of the form, or to display them individually:

.. literalinclude:: model/033.php

Retrieving Validation Rules
---------------------------

You can retrieve a model's validation rules by accessing its ``validationRules``
property:

.. literalinclude:: model/035.php

You can also retrieve just a subset of those rules by calling the accessor
method directly, with options:

.. literalinclude:: model/036.php

The ``$options`` parameter is an associative array with one element,
whose key is either ``'except'`` or ``'only'``, and which has as its
value an array of fieldnames of interest:

.. literalinclude:: model/037.php

Validation Placeholders
-----------------------

The model provides a simple method to replace parts of your rules based on data that's being passed into it. This
sounds fairly obscure but can be especially handy with the ``is_unique`` validation rule. Placeholders are simply
the name of the field (or array key) that was passed in as ``$data`` surrounded by curly brackets. It will be
replaced by the **value** of the matched incoming field. An example should clarify this:

.. literalinclude:: model/038.php

.. note:: Since v4.3.5, you must set the validation rules for the placeholder
    field (``id``).

In this set of rules, it states that the email address should be unique in the database, except for the row
that has an id matching the placeholder's value. Assuming that the form POST data had the following:

.. literalinclude:: model/039.php

then the ``{id}`` placeholder would be replaced with the number **4**, giving this revised rule:

.. literalinclude:: model/040.php

So it will ignore the row in the database that has ``id=4`` when it verifies the email is unique.

.. note:: Since v4.3.5, if the placeholder (``id``) value does not pass the
    validation, the placeholder would not be replaced.

This can also be used to create more dynamic rules at runtime, as long as you take care that any dynamic
keys passed in don't conflict with your form data.

Protecting Fields
=================

To help protect against Mass Assignment Attacks, the Model class **requires** that you list all of the field names
that can be changed during inserts and updates in the `$allowedFields`_ class property. Any data provided
in addition to these will be removed prior to hitting the database. This is great for ensuring that timestamps,
or primary keys do not get changed.

.. literalinclude:: model/041.php

Occasionally, you will find times where you need to be able to change these elements. This is often during
testing, migrations, or seeds. In these cases, you can turn the protection on or off:

.. literalinclude:: model/042.php

Runtime Return Type Changes
===========================

You can specify the format that data should be returned as when using the **find*()** methods as the class property,
`$returnType`_. There may be times that you would like the data back in a different format, though. The Model
provides methods that allow you to do just that.

.. note:: These methods only change the return type for the next **find*()** method call. After that,
    it is reset to its default value.

asArray()
---------

Returns data from the next **find*()** method as associative arrays:

.. literalinclude:: model/047.php

asObject()
----------

Returns data from the next **find*()** method as standard objects or custom class instances:

.. literalinclude:: model/048.php

Processing Large Amounts of Data
================================

Sometimes, you need to process large amounts of data and would run the risk of running out of memory.
To make this simpler, you may use the chunk() method to get smaller chunks of data that you can then
do your work on. The first parameter is the number of rows to retrieve in a single chunk. The second
parameter is a Closure that will be called for each row of data.

This is best used during cronjobs, data exports, or other large tasks.

.. literalinclude:: model/049.php

.. _model-events-callbacks:

Working with Query Builder
**************************

Getting Query Builder for the Model's Table
===========================================

CodeIgniter Model has one instance of the Query Builder for that model's database connection.
You can get access to the **shared** instance of the Query Builder any time you need it:

.. literalinclude:: model/043.php

This builder is already set up with the model's `$table`_.

.. note:: Once you get the Query Builder instance, you can call methods of the
    :doc:`Query Builder <../database/query_builder>`.
    However, since Query Builder is not a Model, you cannot call methods of the Model.

Getting Query Builder for Another Table
=======================================

If you need access to another table, you can get another instance of the Query Builder.
Pass the table name in as a parameter, but be aware that this will **not** return
a shared instance:

.. literalinclude:: model/044.php

Mixing Methods of Query Builder and Model
=========================================

You can also use Query Builder methods and the Model's CRUD methods in the same chained call, allowing for
very elegant use:

.. literalinclude:: model/045.php

In this case, it operates on the shared instance of the Query Builder held by the model.

.. important:: The Model does not provide a perfect interface to the Query Builder.
    The Model and the Query Builder are separate classes with different purposes.
    They should not be expected to return the same data.

If the Query Builder returns a result, it is returned as is.
In that case, the result may be different from the one returned by the model's method
and may not be what was expected. The model's events are not triggered.

To prevent unexpected behavior, do not use Query Builder methods that return results
and specify the model's method at the end of the method chaining.

.. note:: You can also access the model's database connection seamlessly:

    .. literalinclude:: model/046.php

.. _model-events:

Model Events
************

There are several points within the model's execution that you can specify multiple callback methods to run.
These methods can be used to normalize data, hash passwords, save related entities, and much more.

The following
points in the model's execution can be affected, each through a class property:

- `$beforeInsert`_, `$afterInsert`_
- `$beforeUpdate`_, `$afterUpdate`_
- `$beforeFind`_, `$afterFind`_
- `$beforeDelete`_, `$afterDelete`_
- `$beforeInsertBatch`_, `$afterInsertBatch`_
- `$beforeUpdateBatch`_, `$afterUpdateBatch`_

.. note:: ``$beforeInsertBatch``, ``$afterInsertBatch``, ``$beforeUpdateBatch`` and
    ``$afterUpdateBatch`` can be used since v4.3.0.

Defining Callbacks
==================

You specify the callbacks by first creating a new class method in your model to use.

This class method will always receive a ``$data`` array as its only parameter.

The exact contents of the ``$data`` array will vary between events, but will always
contain a key named ``data`` that contains the primary data passed to the original
method. In the case of the **insert*()** or **update*()** methods, that will be
the key/value pairs that are being inserted into the database. The main ``$data``
array will also contain the other values passed to the method, and be detailed
in `Event Parameters`_.

The callback method must return the original ``$data`` array so other callbacks
have the full information.

.. literalinclude:: model/050.php

Specifying Callbacks To Run
===========================

You specify when to run the callbacks by adding the method name to the appropriate class property (`$beforeInsert`_, `$afterUpdate`_,
etc). Multiple callbacks can be added to a single event and they will be processed one after the other. You can
use the same callback in multiple events:

.. literalinclude:: model/051.php

Additionally, each model may allow (default) or deny callbacks class-wide by setting its `$allowCallbacks`_ property:

.. literalinclude:: model/052.php

You may also change this setting temporarily for a single model call using the ``allowCallbacks()`` method:

.. literalinclude:: model/053.php

Event Parameters
================

Since the exact data passed to each callback varies a bit, here are the details on what is in the ``$data`` parameter
passed to each event:

================= =========================================================================================================
Event             $data contents
================= =========================================================================================================
beforeInsert      **data** = the key/value pairs that are being inserted. If an object or Entity class is passed to the
                  ``insert()`` method, it is first converted to an array.
afterInsert       **id** = the primary key of the new row, or 0 on failure.
                  **data** = the key/value pairs being inserted.
                  **result** = the results of the ``insert()`` method used through the Query Builder.
beforeUpdate      **id** = the array of primary keys of the rows being passed to the ``update()`` method.
                  **data** = the key/value pairs that are being updated. If an object or Entity class is passed to the
                  ``update()`` method, it is first converted to an array.
afterUpdate       **id** = the array of primary keys of the rows being passed to the ``update()`` method.
                  **data** = the key/value pairs being updated.
                  **result** = the results of the ``update()`` method used through the Query Builder.
beforeFind        The name of the calling **method**, whether a **singleton** was requested, and these additional fields:
- ``first()``     No additional fields
- ``find()``      **id** = the primary key of the row being searched for.
- ``findAll()``   **limit** = the number of rows to find.
                  **offset** = the number of rows to skip during the search.
afterFind         Same as **beforeFind** but including the resulting row(s) of data, or null if no result found.
beforeDelete      **id** = an array of primary key rows being passed to the ``delete()`` method.
                  **purge** = boolean whether soft-delete rows should be hard deleted.
afterDelete       **id** = an array of primary key rows being passed to the ``delete()`` method.
                  **purge** = boolean whether soft-delete rows should be hard deleted.
                  **result** = the result of the ``delete()`` call on the Query Builder.
                  **data** = unused.
beforeInsertBatch **data** = associative array of values that are being inserted. If an object or Entity class is passed to the
                  ``insertBatch()`` method, it is first converted to an array.
afterInsertBatch  **data** = the associative array of values being inserted.
                  **result** = the results of the ``insertbatch()`` method used through the Query Builder.
beforeUpdateBatch **data** = associative array of values that are being updated. If an object or Entity class is passed to the
                  ``updateBatch()`` method, it is first converted to an array.
afterUpdateBatch  **data** = the key/value pairs being updated.
                  **result** = the results of the ``updateBatch()`` method used through the Query Builder.
================= =========================================================================================================

.. note:: When using the ``paginate()`` method in combination with the ``beforeFind`` event to modify the query,
   the results may not behave as expected.

   This is because the ``beforeFind`` event only affects the actual retrieval of the results (``findAll()``),
   but **not** the query used to count the total number of rows for pagination.

   As a result, the total row count used for generating pagination links may not reflect the modified query conditions,
   leading to inconsistencies in pagination.

Modifying Find* Data
====================

The ``beforeFind`` and ``afterFind`` methods can both return a modified set of data to override the normal response
from the model. For ``afterFind`` any changes made to ``data`` in the return array will automatically be passed back
to the calling context. In order for ``beforeFind`` to intercept the find workflow it must also return an additional
boolean, ``returnData``:

.. literalinclude:: model/054.php

Manual Model Creation
*********************

You do not need to extend any special class to create a model for your application. All you need is to get an
instance of the database connection and you're good to go. This allows you to bypass the features CodeIgniter's
Model gives you out of the box, and create a fully custom experience.

.. literalinclude:: model/055.php
