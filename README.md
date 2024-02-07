## Development

### StagingService

The service controls what articles are currently modified relatively to the last submit to Lakat storage.
Internally service keeps track of modified articles in SQL table `lakat_staging`.

#### Usage

`StagingService` is registered in [MediaWiki's service container](https://www.mediawiki.org/wiki/Dependency_Injection) and should be instantiated like any other MediaWiki service using parameter in constructor:

```php
class SomeClass {
    private StagingService $stagingService;

    public function __construct(StagingService $stagingService) {
        $this->stagingService = $stagingService;
    }

    public function someMethod() {
        $this->stagingService->getStagedArticles( 'SomeBranch' );
    }
}
```

#### Methods

* `getStagedArticles` - retrieve list of modified articles
* `stage` - add articles to the list of modified articles
* `unstage` - remove article from the list of modified articles
* `submitStaged` - submit selected articles to Lakat


