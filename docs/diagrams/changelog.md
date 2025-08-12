# Test Changes Timeline

Recent changes to test files in the last 3 commits.

Generated: 2025-08-12 13:28:42

```mermaid
gitgraph
    commit id: "Baseline"
    commit id: "highlight git changes for last..."
    commit id: "🤖 Auto-update test document..."
    commit id: "Changes in testcases"
```

## Detailed Changes

### tests/Acceptance/WebsiteNavigationCest.php

**Added lines:**
- `$I->click('Newpage');`
- `$I->seeInTitle('Newpage | Simple PHP Website (Master)');`
- `$I->see('Newpage', 'h2');`

**Removed lines:**
- ~~`$I->click('New Page');`~~
- ~~`$I->seeInTitle('New Page | Simple PHP Website (Master)');`~~
- ~~`$I->see('New Page', 'h2');`~~

