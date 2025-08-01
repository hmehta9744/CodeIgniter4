###########
URI Routing
###########

.. contents::
    :local:
    :depth: 3

What is URI Routing?
********************

URI Routing associates a URI with a controller's method.

CodeIgniter has two kinds of routing. One is **Defined Route Routing**, and the other is **Auto Routing**.
With Defined Route Routing, you can define routes manually. It allows flexible URL.
Auto Routing automatically routes HTTP requests based on conventions and execute the corresponding controller methods. There is no need to define routes manually.

First, let's look at Defined Route Routing. If you want to use Auto Routing, see :ref:`auto-routing-improved`.

.. _defined-route-routing:

Setting Routing Rules
*********************

Routing rules are defined in the **app/Config/Routes.php** file. In it you'll see that
it creates an instance of the RouteCollection class (``$routes``) that permits you to specify your own routing criteria.
Routes can be specified using placeholders or Regular Expressions.

When you specify a route, you choose a method to corresponding to HTTP verbs (request method).
If you expect a GET request, you use the ``get()`` method:

.. literalinclude:: routing/001.php

A route takes the **Route Path** (URI path relative to the BaseURL. ``/``) on the left,
and maps it to the **Route Handler** (controller and method ``Home::index``) on the right,
along with any parameters that should be passed to the controller.

The controller and method should
be listed in the same way that you would use a static method, by separating the class
and its method with a double-colon, like ``Users::list``.

If that method requires parameters to be
passed to it, then they would be listed after the method name, separated by forward-slashes:

.. literalinclude:: routing/002.php

Examples
========

Here are a few basic routing examples.

A URL containing the word **journals** in the first segment will be mapped to the ``\App\Controllers\Blogs`` class,
and the :ref:`default method <routing-default-method>`, which is usually ``index()``:

.. literalinclude:: routing/006.php

A URL containing the segments **blog/joe** will be mapped to the ``\App\Controllers\Blogs`` class and the ``users()`` method.
The ID will be set to ``34``:

.. literalinclude:: routing/007.php

A URL with **product** as the first segment, and anything in the second will be mapped to the ``\App\Controllers\Catalog`` class
and the ``productLookup()`` method:

.. literalinclude:: routing/008.php

A URL with **product** as the first segment, and a number in the second will be mapped to the ``\App\Controllers\Catalog`` class
and the ``productLookupByID()`` method passing in the match as a variable to the method:

.. literalinclude:: routing/009.php

.. _routing-http-verb-routes:

HTTP verb Routes
================

You can use any standard HTTP verb (GET, POST, PUT, DELETE, OPTIONS, etc):

.. literalinclude:: routing/003.php

You can supply multiple verbs that a route should match by passing them in as an array to the ``match()`` method:

.. literalinclude:: routing/004.php

Specifying Route Handlers
=========================

.. _controllers-namespace:

Controller's Namespace
----------------------

When you specify a controller and method name as a string, if a controller is
written without a leading ``\``, the :ref:`routing-default-namespace` will be
prepended:

.. literalinclude:: routing/063.php

If you put ``\`` at the beginning, it is treated as a fully qualified class name:

.. literalinclude:: routing/064.php

You can also specify the namespace with the ``namespace`` option:

.. literalinclude:: routing/038.php

See :ref:`assigning-namespace` for details.

Array Callable Syntax
---------------------

.. versionadded:: 4.2.0

Since v4.2.0, you can use array callable syntax to specify the controller:

.. literalinclude:: routing/013.php
   :lines: 2-

Or using ``use`` keyword:

.. literalinclude:: routing/014.php
   :lines: 2-

If you forget to add ``use App\Controllers\Home;``, the controller classname is
interpreted as ``\Home``, not ``App\Controllers\Home``.

.. note:: When you use Array Callable Syntax, the classname is always interpreted
    as a fully qualified classname. So :ref:`routing-default-namespace` and
    :ref:`namespace option <assigning-namespace>` have no effect.

Array Callable Syntax and Placeholders
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

If there are placeholders, it will automatically set the parameters in the specified order:

.. literalinclude:: routing/015.php
   :lines: 2-

But the auto-configured parameters may not be correct if you use regular expressions in routes.
In such a case, you can specify the parameters manually:

.. literalinclude:: routing/016.php
   :lines: 2-

Using Closures
--------------

You can use an anonymous function, or Closure, as the destination that a route maps to. This function will be
executed when the user visits that URI. This is handy for quickly executing small tasks, or even just showing
a simple view:

.. literalinclude:: routing/020.php

Specifying Route Paths
======================

Placeholders
------------

A typical route might look something like this:

.. literalinclude:: routing/005.php

In a route, the first parameter contains the URI to be matched, while the second parameter
contains the destination it should be routed to. In the above example, if the literal word
"product" is found in the first segment of the URL path, and a number is found in the second segment,
the ``Catalog`` class and the ``productLookup`` method are used instead.

Placeholders are simply strings that represent a Regular Expression pattern. During the routing
process, these placeholders are replaced with the value of the Regular Expression. They are primarily
used for readability.

The following placeholders are available for you to use in your routes:

============ ===========================================================================================================
Placeholders Description
============ ===========================================================================================================
(:any)       will match all characters from that point to the end of the URI. This may include multiple URI segments.
(:segment)   will match any character except for a forward slash (``/``) restricting the result to a single segment.
(:num)       will match any positive integer.
(:alpha)     will match any string of alphabetic characters
(:alphanum)  will match any string of alphabetic characters or integers, or any combination of the two.
(:hash)      is the same as ``(:segment)``, but can be used to easily see which routes use hashed ids.
============ ===========================================================================================================

.. note:: ``{locale}`` cannot be used as a placeholder or other part of the route, as it is reserved for use
    in :doc:`localization </outgoing/localization>`.

.. _routing-placeholder-any:

The Behavior of (:any)
^^^^^^^^^^^^^^^^^^^^^^

Note that a single ``(:any)`` will match multiple segments in the URL if present.

For example the route:

.. literalinclude:: routing/010.php

will match **product/123**, **product/123/456**, **product/123/456/789** and so on.

By default, in the above example, if the ``$1`` placeholder contains a slash
(``/``), it will still be split into multiple parameters when passed to
``Catalog::productLookup()``.

.. note:: Since v4.5.0, you can change the behavior with the config option.
    See :ref:`multiple-uri-segments-as-one-parameter` for details.

The implementation in the
Controller should take into account the maximum parameters:

.. literalinclude:: routing/011.php

Or you can use `variable-length argument lists <https://www.php.net/manual/en/functions.arguments.php#functions.variable-arg-list>`_:

.. literalinclude:: routing/068.php

.. important:: Do not put any placeholder after ``(:any)``. Because the number of
    parameters passed to the controller method may change.

If matching multiple segments is not the intended behavior, ``(:segment)`` should be used when defining the
routes. With the examples URLs from above:

.. literalinclude:: routing/012.php

will only match **product/123** and generate 404 errors for other example.

Custom Placeholders
-------------------

You can create your own placeholders that can be used in your routes file to fully customize the experience
and readability.

You add new placeholders with the ``addPlaceholder()`` method. The first parameter is the string to be used as
the placeholder. The second parameter is the Regular Expression pattern it should be replaced with.
This must be called before you add the route:

.. literalinclude:: routing/017.php

Regular Expressions
-------------------

If you prefer you can use regular expressions to define your routing rules. Any valid regular expression
is allowed, as are back-references.

.. important:: Note: If you use back-references you must use the dollar syntax rather than the double backslash syntax.
    A typical RegEx route might look something like this:

    .. literalinclude:: routing/018.php

In the above example, a URI similar to **products/shirts/123** would instead call the ``show()`` method
of the ``Products`` controller class, with the original first and second segment passed as arguments to it.

With regular expressions, you can also catch a segment containing a forward slash (``/``), which would usually
represent the delimiter between multiple segments.

For example, if a user accesses a password protected area of your web application and you wish to be able to
redirect them back to the same page after they log in, you may find this example useful:

.. literalinclude:: routing/019.php

By default, in the above example, if the ``$1`` placeholder contains a slash
(``/``), it will still be split into multiple parameters when passed to
``Auth::login()``.

.. note:: Since v4.5.0, you can change the behavior with the config option.
    See :ref:`multiple-uri-segments-as-one-parameter` for details.

For those of you who don't know regular expressions and want to learn more about them,
`regular-expressions.info <https://www.regular-expressions.info/>`_ might be a good starting point.

.. note:: You can also mix and match placeholders with regular expressions.

.. _view-routes:

View Routes
===========

.. versionadded:: 4.3.0

If you just want to render a view out that has no logic associated with it, you can use the ``view()`` method.
This is always treated as GET request.
This method accepts the name of the view to load as the second parameter.

.. literalinclude:: routing/065.php

If you use placeholders within your route, you can access them within the view in a special variable, ``$segments``.
They are available as an array, indexed in the order they appear in the route.

.. literalinclude:: routing/066.php

.. _redirecting-routes:

Redirecting Routes
==================

Any site that lives long enough is bound to have pages that move. You can specify routes that should redirect
to other routes with the ``addRedirect()`` method. The first parameter is the URI pattern for the old route. The
second parameter is either the new URI to redirect to, or the name of a named route. The third parameter is
the HTTP status code that should be sent along with the redirect. The default value is ``302`` which is a temporary
redirect and is recommended in most cases:

.. literalinclude:: routing/022.php

.. note:: Since v4.2.0, ``addRedirect()`` can use placeholders.

If a redirect route is matched during a page load, the user will be immediately redirected to the new page before a
controller can be loaded.

Environment Restrictions
========================

You can create a set of routes that will only be viewable in a certain environment. This allows you to create
tools that only the developer can use on their local machines that are not reachable on testing or production servers.
This can be done with the ``environment()`` method. The first parameter is the name of the environment. Any
routes defined within this closure are only accessible from the given environment:

.. literalinclude:: routing/028.php

Routes with any HTTP verbs
==========================

.. important:: This method exists only for backward compatibility. Do not use it
    in new projects. Even if you are already using it, we recommend that you use
    another, more appropriate method.

.. warning:: While the ``add()`` method seems to be convenient, it is recommended to always use the HTTP-verb-based
    routes, described above, as it is more secure. If you use the :doc:`CSRF protection </libraries/security>`, it does not protect **GET**
    requests. If the URI specified in the ``add()`` method is accessible by the GET method, the CSRF protection
    will not work.

It is possible to define a route with any HTTP verbs.
You can use the ``add()`` method:

.. literalinclude:: routing/031.php

.. note:: Using the HTTP-verb-based routes will also provide a slight performance increase, since
    only routes that match the current request method are stored, resulting in fewer routes to scan through
    when trying to find a match.

Mapping Multiple Routes
=======================

.. important:: This method exists only for backward compatibility. Do not use it
    in new projects. Even if you are already using it, we recommend that you use
    another, more appropriate method.

.. warning:: The ``map()`` method is not recommended as well as ``add()``
    because it calls ``add()`` internally.

While the ``add()`` method is simple to use, it is often handier to work with multiple routes at once, using
the ``map()`` method. Instead of calling the ``add()`` method for each route that you need to add, you can
define an array of routes and then pass it as the first parameter to the ``map()`` method:

.. literalinclude:: routing/021.php

.. _command-line-only-routes:

Command-Line Only Routes
========================

.. note:: It is recommended to use Spark Commands for CLI scripts instead of calling controllers via CLI.
    See the :doc:`../cli/cli_commands` page for detailed information.

Any route created by any of the HTTP-verb-based
route methods will also be inaccessible from the CLI, but routes created by the ``add()`` method will still be
available from the command line.

You can create routes that work only from the command-line, and are inaccessible from the web browser, with the
``cli()`` method:

.. literalinclude:: routing/032.php

.. warning:: If you enable :ref:`auto-routing-legacy` and place the command file in **app/Controllers**,
    anyone could access the command with the help of Auto Routing (Legacy) via HTTP.

Global Options
**************

All of the methods for creating a route (``get()``, ``post()``, :doc:`resource() <restful>` etc) can take an array of options that
can modify the generated routes, or further restrict them. The ``$options`` array is always the last parameter:

.. literalinclude:: routing/033.php

.. _applying-filters:

Applying Filters
================

You can alter the behavior of specific routes by supplying filters to run before or after the controller. This is especially handy during authentication or api logging.

The value for the filter can be a string or an array of strings:

* matching the aliases defined in **app/Config/Filters.php**.
* filter classnames

See :ref:`Controller Filters <filters-aliases>` for more information on defining aliases.

.. Warning:: If you set filters to routes in **app/Config/Routes.php**
    (not in **app/Config/Filters.php**), it is recommended to disable Auto Routing (Legacy).
    When :ref:`auto-routing-legacy` is enabled, it may be possible that a controller can be accessed
    via a different URL than the configured route,
    in which case the filter you specified to the route will not be applied.
    See :ref:`use-defined-routes-only` to disable auto-routing.

Alias Filter
------------

You specify an alias :ref:`defined in app/Config/Filters.php <filters-aliases>` for the filter value:

.. literalinclude:: routing/034.php

You may also supply arguments to be passed to the alias filter's ``before()`` and ``after()`` methods:

.. literalinclude:: routing/035.php

Classname Filter
----------------

.. versionadded:: 4.1.5

You can specify a filter classname for the filter value:

.. literalinclude:: routing/036.php

.. _multiple-filters:

Multiple Filters
----------------

.. versionadded:: 4.1.5

.. important:: Since v4.5.0, *Multiple Filters* are always enabled.
    Prior to v4.5.0, *Multiple Filters* were disabled by default.
    If you want to use with prior to v4.5.0, See
    :ref:`Upgrading from 4.1.4 to 4.1.5 <upgrade-415-multiple-filters-for-a-route>`
    for the details.

You can specify an array for the filter value:

.. literalinclude:: routing/037.php

Filter Arguments
^^^^^^^^^^^^^^^^

Additional arguments may be passed to a filter:

.. literalinclude:: routing/067.php

In this example, the array ``['dual', 'noreturn']`` will be passed in ``$arguments``
to the filter's ``before()`` and ``after()`` implementation methods.

.. _assigning-namespace:

Assigning Namespace
===================

While a :ref:`routing-default-namespace` will be prepended to the generated controllers, you can also specify
a different namespace to be used in any options array, with the ``namespace`` option. The value should be the
namespace you want modified:

.. literalinclude:: routing/038.php

The new namespace is only applied during that call for any methods that create a single route, like get, post, etc.
For any methods that create multiple routes, the new namespace is attached to all routes generated by that function
or, in the case of ``group()``, all routes generated while in the closure.

Limit to Hostname
=================

You can restrict groups of routes to function only in certain domain or sub-domains of your application
by passing the "hostname" option along with the desired domain to allow it on as part of the options array:

.. literalinclude:: routing/039.php

This example would only allow the specified hosts to work if the domain exactly matched **accounts.example.com**.
It would not work under the main site at **example.com**.

Restrict by Multiple Hostnames
------------------------------

.. versionadded:: 4.6.0

Also you can restrict by multiple hostnames, e.g:

.. literalinclude:: routing/073.php

Limit to Subdomains
===================

When the ``subdomain`` option is present, the system will restrict the routes to only be available on that
sub-domain. The route will only be matched if the subdomain is the one the application is being viewed through:

.. literalinclude:: routing/040.php

You can restrict it to any subdomain by setting the value to an asterisk, (``*``). If you are viewing from a URL
that does not have any subdomain present, this will not be matched:

.. literalinclude:: routing/041.php

.. important:: The system is not perfect and should be tested for your specific domain before being used in production.
    Most domains should work fine but some edge case ones, especially with a period in the domain itself (not used
    to separate suffixes or www) can potentially lead to false positives.

Offsetting the Matched Parameters
=================================

You can offset the matched parameters in your route by any numeric value with the ``offset`` option, with the
value being the number of segments to offset.

This can be beneficial when developing APIs with the first URI segment being the version number. It can also
be used when the first parameter is a language string:

.. literalinclude:: routing/042.php

.. _reverse-routing:

Reverse Routing
***************

Reverse routing allows you to define the controller and method, as well as any parameters, that a link should go
to, and have the router lookup the current route to it. This allows route definitions to change without you having
to update your application code. This is typically used within views to create links.

For example, if you have a route to a photo gallery that you want to link to, you can use the :php:func:`url_to()` helper
function to get the route that should be used. The first parameter is the fully qualified Controller and method,
separated by a double colon (``::``), much like you would use when writing the initial route itself. Any parameters that
should be passed to the route are passed in next:

.. literalinclude:: routing/029.php

.. _using-named-routes:

Named Routes
************

You can name routes to make your application less fragile. This applies a name to a route that can be called
later, and even if the route definition changes, all of the links in your application built with :php:func:`url_to()`
will still work without you having to make any changes. A route is named by passing in the ``as`` option
with the name of the route:

.. literalinclude:: routing/030.php

This has the added benefit of making the views more readable, too.

.. note:: By default, all defined routes have names matching their paths, with placeholders replaced by their corresponding
    regular expressions. For example, if you define a route like ``$routes->get('edit/(:num)', 'PostController::edit/$1');``,
    you can generate the corresponding URL using ``route_to('edit/([0-9]+)', 12)``.

.. warning:: According to :ref:`routing-priority`, if a not-named route is defined first (e.g., ``$routes->get('edit', 'PostController::edit');``)
    and another named route is defined later with the same name as the path of the first route (e.g., ``$routes->get('edit/(:num)', 'PostController::edit/$1', ['as' => 'edit']);``),
    the second route will not be registered because its name will conflict with the automatically assigned name of the first route.

Grouping Routes
***************

You can group your routes under a common name with the ``group()`` method. The group name becomes a segment that
appears prior to the routes defined inside of the group. This allows you to reduce the typing needed to build out an
extensive set of routes that all share the opening string, like when building an admin area:

.. literalinclude:: routing/023.php

This would prefix the **users** and **blog** URIs with **admin**, handling URLs like **admin/users** and **admin/blog**.

Setting Namespace
=================

If you need to assign options to a group, like a :ref:`assigning-namespace`, do it before the callback:

.. literalinclude:: routing/024.php

This would handle a resource route to the ``App\API\v1\Users`` controller with the **api/users** URI.

Setting Filters
===============

You can also use a specific :doc:`filter <filters>` for a group of routes. This will always
run the filter before or after the controller. This is especially handy during authentication or api logging:

.. literalinclude:: routing/025.php

The value for the filter must match one of the aliases defined within **app/Config/Filters.php**.

.. note:: Prior to v4.5.4, due to a bug, filters passed to the ``group()`` were
    not merged into the filters passed to the inner routes.

Setting Other Options
=====================

At some point, you may want to group routes for the purpose of applying filters or other route
config options like namespace, subdomain, etc. Without necessarily needing to add a prefix to the group, you can pass
an empty string in place of the prefix and the routes in the group will be routed as though the group never existed but with the
given route config options:

.. literalinclude:: routing/027.php

.. _routing-nesting-groups:

Nesting Groups
==============

It is possible to nest groups within groups for finer organization if you need it:

.. literalinclude:: routing/026.php

This would handle the URL at **admin/users/list**.

The ``filter`` option passed to the outer ``group()`` are merged with the inner
``group()`` filter option.
The above code runs ``myfilter1:config`` for the route ``admin``, and ``myfilter1:config``
and ``myfilter2:region`` for the route ``admin/users/list``.

.. note:: Prior to v4.6.0, the same filter cannot be run multiple times with
    different arguments.

Any other overlapping options passed to the inner ``group()`` will overwrite their values.

.. note:: Prior to v4.5.0, due to a bug, options passed to the outer ``group()``
    are not merged with the inner ``group()`` options.

.. _routing-priority:

Route Priority
**************

Routes are registered in the routing table in the order in which they are defined. This means that when a URI is accessed, the first matching route will be executed.

.. warning:: If a route path is defined more than once with different handlers, only the first defined route is registered.

You can check registered routes in the routing table by running the :ref:`spark routes <routing-spark-routes>` command.

Changing Route Priority
=======================

When working with modules, it can be a problem if the routes in the application contain wildcards.
Then the module routes will not be processed correctly.
You can solve this problem by lowering the priority of route processing using the ``priority`` option. The parameter
accepts positive integers and zero. The higher the number specified in the ``priority``, the lower
route priority in the processing queue:

.. literalinclude:: routing/043.php

To disable this functionality, you must call the method with the parameter ``false``:

.. literalinclude:: routing/044.php

.. note:: By default, all routes have a priority of 0.
    Negative integers will be cast to the absolute value.

.. _routes-configuration-options:

Routes Configuration Options
****************************

The RoutesCollection class provides several options that affect all routes, and can be modified to meet your
application's needs. These options are available in **app/Config/Routing.php**.

.. note:: The config file **app/Config/Routing.php** has been added since v4.4.0.
    In previous versions, the setter methods were used in **app/Config/Routes.php**
    to change settings.

.. _routing-default-namespace:

Default Namespace
=================

When matching a controller to a route, the router will add the default namespace value to the front of the controller
specified by the route. By default, this value is ``App\Controllers``.

If you set the value empty string (``''``), it leaves each route to specify the fully namespaced
controller:

.. literalinclude:: routing/045.php

If your controllers are not explicitly namespaced, there is no need to change this. If you namespace your controllers,
then you can change this value to save typing:

.. literalinclude:: routing/046.php

.. _routing-default-method:

Default Method
==============

This setting is used when the route handler only has the controller name and no
method name listed. The default value is ``index``.
::

    // In app/Config/Routing.php
    public string $defaultMethod = 'index';

.. note:: The ``$defaultMethod`` is also common with Auto Routing.
    See :ref:`Auto Routing (Improved) <routing-auto-routing-improved-default-method>`
    or :ref:`Auto Routing (Legacy) <routing-auto-routing-legacy-default-method>`.

If you define the following route::

    $routes->get('/', 'Home');

the ``index()`` method of the ``App\Controllers\Home`` controller is executed
when the route matches.

.. note:: Method names beginning with ``_`` cannot be used as the default method.
    However, starting with v4.5.0, ``__invoke`` method is allowed.

Translate URI Dashes
====================

This option enables you to automatically replace dashes (``-``) with underscores in the controller and method
URI segments when used in Auto Routing, thus saving you additional route entries if you need to do that. This is required because the dash isn't a valid class or method name character and would cause a fatal error if you try to use it:

.. literalinclude:: routing/049.php

.. note:: When using Auto Routing (Improved), prior to v4.4.0, if
    ``$translateURIDashes`` is true, two URIs correspond to a single controller
    method, one URI for dashes (e.g., **foo-bar**) and one URI for underscores
    (e.g., **foo_bar**). This was incorrect behavior. Since v4.4.0, the URI for
    underscores (**foo_bar**) is not accessible.

.. _use-defined-routes-only:

Use Defined Routes Only
=======================

Since v4.2.0, the auto-routing is disabled by default.

When no defined route is found that matches the URI, the system will attempt to match that URI against the
controllers and methods when Auto Routing is enabled.

You can disable this automatic matching, and restrict routes
to only those defined by you, by setting the ``$autoRoute`` property to false:

.. literalinclude:: routing/050.php

.. warning:: If you use the :doc:`CSRF protection </libraries/security>`, it does not protect **GET**
    requests. If the URI is accessible by the GET method, the CSRF protection will not work.

.. _404-override:

404 Override
============

When a page is not found that matches the current URI, the system will show a
generic 404 view. Using the ``$override404`` property within the routing config
file, you can define controller class/method for 404 routes.

.. literalinclude:: routing/051.php

You can also change what happens by specifying an action to happen with the
``set404Override()`` method in Routes config file. The value can be either a
valid class/method pair, or a Closure:

.. literalinclude:: routing/069.php

.. note:: Starting with v4.5.0, the 404 Override feature sets the Response status
    code to ``404`` by default. In previous versions, the code was ``200``.
    If you want to change the status code in the controller, see
    :php:meth:`CodeIgniter\\HTTP\\Response::setStatusCode()` for information on
    how to set the status code.

Route Processing by Priority
============================

Enables or disables processing of the routes queue by priority. Lowering the priority is defined in the route option.
Disabled by default. This functionality affects all routes.
For an example use of lowering the priority see :ref:`routing-priority`:

.. literalinclude:: routing/052.php

.. _multiple-uri-segments-as-one-parameter:

Multiple URI Segments as One Parameter
======================================

.. versionadded:: 4.5.0

When this option is enabled, a placeholder that matches multiple segments, such
as ``(:any)``, will be passed directly as it is to one parameter, even if it
contains multiple segments.

.. literalinclude:: routing/070.php

For example the route:

.. literalinclude:: routing/010.php

will match **product/123**, **product/123/456**, **product/123/456/789** and so on.
And if the URI is **product/123/456**, ``123/456`` will be passed to the first
parameter of the ``Catalog::productLookup()`` method.

Auto Routing (Improved)
***********************

.. versionadded:: 4.2.0

Auto Routing (Improved) is a new, more secure automatic routing system.

See :doc:`auto_routing_improved` for details.

.. _auto-routing-legacy:

Auto Routing (Legacy)
*********************

.. important:: This feature exists only for backward compatibility. Do not use it
    in new projects. Even if you are already using it, we recommend that you use
    the :ref:`auto-routing-improved` instead.

Auto Routing (Legacy) is a routing system from CodeIgniter 3.
It can automatically route HTTP requests based on conventions and execute the corresponding controller methods.

It is recommended that all routes are defined in the **app/Config/Routes.php** file,
or to use :ref:`auto-routing-improved`,

.. warning:: To prevent misconfiguration and miscoding, we recommend that you do not use
    Auto Routing (Legacy) feature. It is easy to create vulnerable apps where controller filters
    or CSRF protection are bypassed.

.. important:: Auto Routing (Legacy) routes an HTTP request with **any** HTTP method to a controller method.

Enable Auto Routing (Legacy)
============================

Since v4.2.0, the auto-routing is disabled by default.

To use it, you need to change the setting ``$autoRoute`` option to ``true`` in **app/Config/Routing.php**::

    public bool $autoRoute = true;

And set the property ``$autoRoutesImproved`` to ``false`` in **app/Config/Feature.php**::

    public bool $autoRoutesImproved = false;

URI Segments (Legacy)
=====================

The segments in the URL, in following with the Model-View-Controller approach, usually represent::

    example.com/class/method/ID

1. The first segment represents the controller **class** that should be invoked.
2. The second segment represents the class **method** that should be called.
3. The third, and any additional segments, represent the ID and any variables that will be passed to the controller.

Consider this URI::

    example.com/index.php/helloworld/index/1

In the above example, CodeIgniter would attempt to find a controller named **Helloworld.php**
and executes ``index()`` method with passing ``'1'`` as the first argument.

See :ref:`Auto Routing (Legacy) in Controllers <controller-auto-routing-legacy>` for more info.

.. _routing-auto-routing-legacy-configuration-options:

Configuration Options (Legacy)
==============================

These options are available in the **app/Config/Routing.php** file.

Default Controller (Legacy)
---------------------------

For Site Root URI (Legacy)
^^^^^^^^^^^^^^^^^^^^^^^^^^

When a user visits the root of your site (i.e., **example.com**) the controller
to use is determined by the value set to the ``$defaultController`` property,
unless a route exists for it explicitly.

The default value for this is ``Home`` which matches the controller at
**app/Controllers/Home.php**::

    public string $defaultController = 'Home';

For Directory URI (Legacy)
^^^^^^^^^^^^^^^^^^^^^^^^^^

The default controller is also used when no matching route has been found, and the URI would point to a directory
in the controllers directory. For example, if the user visits **example.com/admin**, if a controller was found at
**app/Controllers/Admin/Home.php**, it would be used.

See :ref:`Auto Routing (Legacy) in Controllers <controller-auto-routing-legacy>` for more info.

.. _routing-auto-routing-legacy-default-method:

Default Method (Legacy)
-----------------------

This works similar to the default controller setting, but is used to determine the default method that is used
when a controller is found that matches the URI, but no segment exists for the method. The default value is
``index``.

In this example, if the user were to visit **example.com/products**, and a ``Products``
controller existed, the ``Products::listAll()`` method would be executed::

    public string $defaultMethod = 'listAll';

Confirming Routes
*****************

CodeIgniter has the following :doc:`command </cli/spark_commands>` to display all routes.

.. _routing-spark-routes:

spark routes
============

Displays all routes and filters:

.. code-block:: console

    php spark routes

The output is like the following:

.. code-block:: none

    +---------+---------+---------------+-------------------------------+----------------+---------------+
    | Method  | Route   | Name          | Handler                       | Before Filters | After Filters |
    +---------+---------+---------------+-------------------------------+----------------+---------------+
    | GET     | /       | »             | \App\Controllers\Home::index  |                | toolbar       |
    | GET     | feed    | »             | (Closure)                     |                | toolbar       |
    +---------+---------+---------------+-------------------------------+----------------+---------------+

The *Method* column shows the HTTP method that the route is listening for.

The *Route* column shows the route path to match. The route of a defined route is expressed as a regular expression.

Since v4.3.0, the *Name* column shows the route name. ``»`` indicates the name is the same as the route path.

.. important:: The system is not perfect.
    For routes containing regular expression patterns like ``([^/]+)`` or ``{locale}``,
    the *Filters* displayed might not be correct (if you set complicated URI pattern
    for the filters in **app/Config/Filters.php**), or it is displayed as ``<unknown>``.

    The :ref:`spark filter:check <spark-filter-check>` command can be used to check
    for 100% accurate filters.

Auto Routing (Improved)
-----------------------

When you use Auto Routing (Improved), the output is like the following:

.. code-block:: none

    +-----------+-------------------------+---------------+-----------------------------------+----------------+---------------+
    | Method    | Route                   | Name          | Handler                           | Before Filters | After Filters |
    +-----------+-------------------------+---------------+-----------------------------------+----------------+---------------+
    | GET(auto) | product/list/../..[/..] |               | \App\Controllers\Product::getList |                | toolbar       |
    +-----------+-------------------------+---------------+-----------------------------------+----------------+---------------+

The *Method* will be like ``GET(auto)``.

``/..`` in the *Route* column indicates one segment. ``[/..]`` indicates it is optional.

.. note:: When auto-routing is enabled and you have the route ``home``, it can be also accessed by ``Home``, or maybe by ``hOme``, ``hoMe``, ``HOME``, etc. but the command will show only ``home``.

If you see a route starting with ``x`` like the following, it indicates an invalid
route that won't be routed, but the controller has a public method for routing.

.. code-block:: none

    +-----------+----------------+------+-------------------------------------+----------------+---------------+
    | Method    | Route          | Name | Handler                             | Before Filters | After Filters |
    +-----------+----------------+------+-------------------------------------+----------------+---------------+
    | GET(auto) | x home/foo     |      | \App\Controllers\Home::getFoo       | <unknown>      | <unknown>     |
    +-----------+----------------+------+-------------------------------------+----------------+---------------+

The above example shows you have the ``\App\Controllers\Home::getFoo()`` method,
but it is not routed because it is the default controller (``Home`` by default)
and the default controller name must be omitted in the URI. You should delete
the ``getFoo()`` method.

.. note:: Prior to v4.3.4, the invalid route is displayed as a normal route
    due to a bug.

Auto Routing (Legacy)
---------------------

When you use Auto Routing (Legacy), the output is like the following:

.. code-block:: none

    +--------+--------------------+---------------+-----------------------------------+----------------+---------------+
    | Method | Route              | Name          | Handler                           | Before Filters | After Filters |
    +--------+--------------------+---------------+-----------------------------------+----------------+---------------+
    | auto   | product/list[/...] |               | \App\Controllers\Product::getList |                | toolbar       |
    +--------+--------------------+---------------+-----------------------------------+----------------+---------------+

The *Method* will be ``auto``.

``[/...]`` in the *Route* column indicates any number of segments.

.. note:: When auto-routing is enabled and you have the route ``home``, it can be also accessed by ``Home``, or maybe by ``hOme``, ``hoMe``, ``HOME``, etc. but the command will show only ``home``.

.. _routing-spark-routes-sort-by-handler:

Sort by Handler
---------------

.. versionadded:: 4.3.0

You can sort the routes by *Handler*:

.. code-block:: console

    php spark routes -h

.. _routing-spark-routes-specify-host:

Specify Host
------------

.. versionadded:: 4.4.0

You can specify the host in the request URL with the ``--host`` option:

.. code-block:: console

    php spark routes --host accounts.example.com

Getting Routing Information
***************************

In CodeIgniter 4, understanding and managing routing information is crucial for handling HTTP requests effectively.
This involves retrieving details about the active controller and method, as well as the filters applied to a specific route.
Below, we explore how to access this routing information to assist in tasks such as logging, debugging, or implementing conditional logic.

Retrieving the Current Controller/Method Names
==============================================

In some cases, you might need to determine which controller and method have been triggered by the current HTTP request.
This can be useful for logging, debugging, or conditional logic based on the active controller method.

CodeIgniter 4 provides a simple way to access the current route's controller and method names using the ``Router`` class. Here is an example:

.. literalinclude:: routing/071.php

This functionality is particularly useful when you need to dynamically interact with your controller or log which method is handling a particular request.

Getting Active Filters for the Current Route
============================================

:doc:`Filters <filters>` are a powerful feature that enables you to perform operations such as authentication, logging, and security checks before or after processing HTTP requests.
To access the active filters for a specific route, you can use the :php:meth:`CodeIgniter\\Router\\Router::getFilters()` method from the ``Router`` class.

This method returns a list of filters that are currently active for the route being processed:

.. literalinclude:: routing/072.php

.. note:: The ``getFilters()`` method returns only the filters defined for the specific route.
     It does not include global filters or those specified in the **app/Config/Filters.php** file.

Getting Matched Route Options for the Current Route
===================================================

When we're defining routes, they may have optional parameters: ``filter``, ``namespace``, ``hostname``, ``subdomain``, ``offset``, ``priority``, ``as``. All of them were described earlier above.
Additionally, if we use ``addRedirect()`` we can also expect the ``redirect`` key.
To access the values of these parameters, we can call ``Router::getMatchedRouteOptions()``. Here is an example of the returned array:

.. literalinclude:: routing/074.php

