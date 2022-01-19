# PHP-LMDB
This is a OOP project wrapping the `dba_*` functions to communicate with [LMDB](http://www.lmdb.tech/doc/index.html).

## Installation
To install this, you must have first installed `lmdb`. Next you *must* compile PHP with the flag `--with-lmdb=DIR`.

### MacOS (homebrew)
1) You need to install lmdb: `brew install lmdb`.
2) You will need to manually edit the php formula. `brew edit php@7.4` (or whatever PHP version)
3) Add `depends_on "lmdb"` after the other dependencies.
4) Add `--with-lmdb=/opt/homebrew/opt/lmdb` somewhere in the `args`. I added it just after `--with-ndbm`.
5) Next up you need to install dependencies to be able to build PHP from source:

    `brew install autoconf automake bison freetype gettext icu4c krb5 libedit libiconv libjpeg libpng libxml2 libzip pkg-config re2c zlib openssl postgresql`.

6) Build php: `brew reinstall --build-from-source php@7.4` (assumes you already have php@7.4). This could take some time.
7. Test it - now when running `print_r(dba_handler())` should include `lmdb`.

### Linux:

TBD.
