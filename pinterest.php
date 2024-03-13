<?php

/**
 * Pinterest Downloader
 * GitHub: https://github.com/hctilg/pinterest-crawler
 */

/**
 * Pinterest Config
 * @param string $searchKeywords - Search word
 * @param int    $fileLengths    - total number of images to download (default = "100")
 * @param string $imageQuality   - image quality (default = "orig")
 * @param string $bookmarks      - next page data (default= "")
 */
class PinterestConfig {
    const IMAGE_SEARCH_URL = "https://tr.pinterest.com/resource/BaseSearchResource/get/?";
    public $searchKeywords = '';
    public $fileLengths = 100;
    public $imageQuality = "orig";
    public $bookmarks = '';

    public function __construct(
        string $searchKeywords = '',
        int $fileLengths = 100,
        string $imageQuality = "orig",
        string $bookmarks = '',
    ) {
        $this->searchKeywords = $searchKeywords;
        $this->fileLengths = $fileLengths;
        $this->imageQuality = $imageQuality;
        $this->bookmarks = $bookmarks;
    }

    // image search url
    public function getSearchUrl() {
        return self::IMAGE_SEARCH_URL;
    }

    // search parameter "source_url"
    public function getSourceUrl() {
        return "/search/pins/?q=" . urlencode($this->searchKeywords);
    }

    // search parameter "data"
    public function getImageData() {
        if ($this->bookmarks == '') {
            return '{"options":{"isPrefetch":false,"query":"' . $this->searchKeywords . '","scope":"pins","no_fetch_context_on_resource":false},"context":{}}';
        } else {
            return '{"options":{"page_size":25,"query":"' . $this->searchKeywords . '","scope":"pins","bookmarks":["' . $this->bookmarks . '"],"field_set_key":"unauth_react","no_fetch_context_on_resource":false},"context":{}}';
        }
    }

    public function getSearchKeywords() {
        return $this->searchKeywords;
    }

    public function setSearchKeywords($searchKeywords) {
        $this->searchKeywords = $searchKeywords;
    }

    public function getFileLengths() {
        return $this->fileLengths;
    }

    public function setFileLengths($fileLengths) {
        $this->fileLengths = $fileLengths;
    }

    public function getImageQuality() {
        return $this->imageQuality;
    }

    public function setImageQuality($imageQuality) {
        $this->imageQuality = $imageQuality;
    }

    public function getBookmarks() {
        return $this->bookmarks;
    }

    public function setBookmarks($bookmarks) {
        $this->bookmarks = $bookmarks;
    }
}

class PinterestScraper {
    private $config;
    private $image_urls;

    public function __construct($config, array $image_urls=[]) {
        $this->config = $config;
        $this->image_urls = $image_urls;
    }

    // Set config for bookmarks (next page)
    public function setConfig($config) {
        $this->config = $config;
    }

    // Download images
    public function downloadImages() {
        $folder = "photos/" . str_replace(" ", "-", $this->config->searchKeywords);
        $number = 0;

        // prev get links
        $results = $this->getUrls();
        if (!file_exists($folder)) {
            mkdir($folder, 0777, true);
            echo "Directory " . $folder . " Created \n";
        }

        $arr = scandir($folder);
        foreach ($results as $i) {
            if (!in_array($i . ".jpg", $arr)) {
                try {
                    $sfe = explode('/', $i);
                    $fileName = end($sfe);
                    $downloadFolder = $folder . "/" . $fileName;
                    echo "Download ::: " . $i . "\n";
                    @file_put_contents($downloadFolder, @file_get_contents($i));
                    $number++;
                } catch (Exception $e) {
                    echo "Error ::: " . $e->getMessage() . "\n";
                }
            }
        }
    }

    // getUrls return array
    public function getUrls() {
        $SOURCE_URL = $this->config->getSourceUrl();
        $DATA = $this->config->getImageData();
        $URL_CONSTANT = $this->config->getSearchUrl();
        $query_data = ['source_url'=> $SOURCE_URL, 'data'=> $DATA];
        $stream = stream_context_create(['http'=> ['method'=> 'GET', 'content'=> http_build_query($query_data)]]);
        $response = @file_get_contents($URL_CONSTANT, false, $stream);
        $jsonData = json_decode($response, true);
        $resourceResponse = $jsonData["resource_response"];
        $data = $resourceResponse["data"];
        $results = $data["results"] ?? [];
        
        foreach ($results as $result) {
            try {
                $this->image_urls[] = $result["images"][$this->config->imageQuality]["url"];
            } catch (Exception $e) {}
        }

        if (count($this->image_urls) < (int)$this->config->fileLengths) {
            $this->config->bookmarks = $resourceResponse["bookmark"];
            echo "Creating links " . count($this->image_urls) . "\n";
            $this->getUrls();
        }
        
        return array_slice($this->image_urls, 0, $this->config->fileLengths);
    }
}
