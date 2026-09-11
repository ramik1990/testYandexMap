<?php

namespace Tests\Unit\YandexMaps;

use App\Services\YandexMaps\RequestSigner;
use PHPUnit\Framework\TestCase;

class RequestSignerTest extends TestCase
{
    public function test_hash_matches_signature_observed_in_browser(): void
    {
        $query = 'ajax=1&businessId=1124715036&csrfToken=7eb8f1bfd80f7fc77573fafd697047750b4a8a74%3A1789140889&locale=ru_KZ&page=2&pageSize=50&ranking=by_relevance_org&reqId=1789140889066060-4093445712-addrs-upper-yp-79&sessionId=1789140888995478-15493630676984512942-balancer-l7leveler-kubr-yp-sas-68-BAL';

        $this->assertSame(2891569646, (new RequestSigner())->hash($query));
    }

    public function test_signed_query_appends_signature_of_encoded_query(): void
    {
        $signer = new RequestSigner();
        $signed = $signer->signedQuery(['ajax' => 1, 'csrfToken' => 'a:b', 'page' => 2]);

        $this->assertSame('ajax=1&csrfToken=a%3Ab&page=2&s='.$signer->hash('ajax=1&csrfToken=a%3Ab&page=2'), $signed);
    }
}
