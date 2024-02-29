## Installation

* Download and move Lakat extension files to `extensions/Lakat` directory in your MediaWiki installation.

* Add the following code at the bottom of your `LocalSettings.php` file:
  ```php
  wfLoadExtension( 'Lakat' );
  ```

* Run the [update script](https://www.mediawiki.org/wiki/Manual:Update.php) which will automatically create necessary database tables that this extension needs.
  ```
  php maintenance/run.php update
  ```

* Done – Navigate to **Special:Version** on your wiki to verify that the extension is successfully installed.

## Development

### Database

SQL files to create necessary tables can be found in `sql/` subdirectory. Run [update script](https://www.mediawiki.org/wiki/Manual:Update.php) when database schema update is necessary.

### Permissions

Access permissions are defined in [extension.json](./extension.json), e.g.:

```json
	"AvailableRights": [
		"lakat-createbranch"
	],
	"GroupPermissions": {
		"user": {
			"lakat-createbranch": true
		}
	},
```

See [Manual:$wgAvailableRights](https://www.mediawiki.org/wiki/Manual:$wgAvailableRights) for details.

Permissions defined in this way can be used in special pages by providing it in the constructor:

```php
    parent::__construct( 'CreateBranch', 'lakat-createbranch' );
```

Additionally, special page can require user log in. Method `SpecialPage::requireNamedUser()` can be used to conveniently show log in form.

### Service container

[Dependency injection in MediaWiki](https://www.mediawiki.org/wiki/Dependency_Injection) is implemented in service container class `MediaWikiServices`. It is responsible for creation of all service classes, including those from extensions.

To find out what services are defined in Lakat extension look at following files:
* [ServiceWiring.php](./src/ServiceWiring.php) - defines how services are created by mapping service names to instantiation callbacks
* [LakatServices.php](./src/LakatServices.php) - defines static functions for easy access to service, e.g. `LakatServices::getStagingService()`

Constructor parameter is the recommended best practice to inject service in your class and should be used instead of direct instantiation when possible, e.g.:
```php
class SomeClass {
    private StagingService $stagingService;

    public function __construct( StagingService $stagingService ) {
        $this->stagingService = $stagingService;
    }

    public function someMethod() {
        $this->stagingService->getStagedArticles( 'SomeBranch' );
    }
}
```

### StagingService

`StagingService` controls what articles are currently modified relatively to the last submit to Lakat storage.
Internally service keeps track of modified articles in SQL table `lakat_staging`.

#### Usage

`StagingService` is registered in MediaWiki's service container and should be instantiated like it is shown above.

#### Methods

* `getStagedArticles` - retrieve list of modified articles
* `stage` - add articles to the list of modified articles
* `unstage` - remove article from the list of modified articles
* `submitStaged` - submit selected articles to Lakat
* `reset` - reset modified article to the state stored in Lakat storage
  * if article doesn't exist on Lakat then delete wiki page
  * otherwise fetch article content from Lakat and save as new revision in wiki page, then unstage article

### SpecialFetchBranch

`SpecialFetchBranch` is a redirect special page which is special kind of special page in MediaWiki. When requesting URL `SpecialFetchBranch/<BranchId>` then branch is fetched from Lakat and stored in wiki page.
