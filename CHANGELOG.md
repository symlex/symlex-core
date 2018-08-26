Changelog
=========

Release 4
---------

* 4.1.0 Moved `Symlex\Kernel\Exception\Exception` to `Symlex\Exception\KernelException` (was only used by `Symlex\Kernel\Apps`)

* 4.0.1 Upgraded symlex/di-microkernel to ^2.0

* 4.0.0 Removed Silex and added Symlex\Application\Web as replacement
    - Router callback parameter order changed slightly (Request is always first)
    - Routers moved to the `Symlex\Router\Web` namespace    
    - Exceptions moved to the `Symlex\Exception` namespace
    - `Symlex\Application\Console` wraps Symfony Console for consistent naming (optional)

Release 3
---------

* 3.5.1 Improved documentation and examples

* 3.5.0 Upgraded Twig to ^2.5.0

* 3.4.0 Upgrade to Symfony 4 and removed support for PHP 7.0

* 3.3.0 Added TwigDefaultRouter for improved single-page app routing

* 3.2.1 Fix for twig router: Settings twig variables after calling router action

* 3.2.0 Added type hints to error router

* 3.1.0 Added PHP type hints and getResponse() method to routers for easy customization

* 3.0.0 Moved kernel from Symlex\Bootstrap to Symlex\Kernel namespace

Release 2
---------

* 2.3.1 Rest router can now handle Response objects from controllers too

* 2.3.0 Moved repository to symlex/symlex-core

* 2.2.0 Upgraded to di-microkernel 1.2

* 2.1.2 Code clean-up

* 2.1.1 Improved REST routing and documentation

* 2.1.0 Using the di-microkernel library and updated docs

* 2.0.3 Removed message from error page, if debug is false

* 2.0.2 Fixed bug in error router

* 2.0.1 Fixed code formatting

* 2.0.0 Upgraded Symlex from Silex 1 to 2

Release 1
---------

* 1.1.2 Improved container caching

* 1.1.1 Removed direct phpunit dependency

* 1.1.0 PHP 7, PHPUnit 6 & PSR-4 compatibility

* 1.0.1 Added support for app.sub_environment config parameter

* 1.0.0 Updated dependencies and added Web App container (WebApps)

Pre-release
-----------

* 0.9 Improved error router

* 0.8 Upgraded dependencies

* 0.7 Added chdir(__DIR__) to default console app kernel

* 0.6 Added HTTP interceptor example (digest auth)

* 0.5 Fixed kernel bug and improved documentation

* 0.4 Refactored kernel exceptions

* 0.3 Improved bootstrap: Caching can be disabled

* 0.2 Fixed dependencies

* 0.1 Initial pre-release