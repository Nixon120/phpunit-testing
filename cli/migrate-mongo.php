<?php
require __DIR__ . '/../vendor/autoload.php';

$mongo = \Services\MongoFactory::getDb();

// Fetch all the collections.
$collections = $mongo->listCollections();

// Iterate thru collections
foreach ($collections as $collection_data) {
    $collection = $mongo
        ->selectCollection(
            $collection_data->getName()
        );

    // Select all documents in collection.
    $documents = $collection->find();

    // Iterate thru all documents in collection.
    foreach ($documents as $document) {
        // If document.response exists
        if (!empty($document['response'])) {
            // copy document.response as temp_response
            $temp_response = $document['response'];
            // Copy document.http_status to temp_response.http_status
            $temp_response['http_status'] = $document['http_status'];
            // Delete document.response
            $collection->findOneAndUpdate(
                ['_id' => $document['_id']],
                ['$unset' => ['response' => '']]
            );
            // Create document.responses (array) with the temp_response
            $collection->findOneAndUpdate(
                ['_id' => $document['_id']],
                ['$set' => ['responses' => [$temp_response]]]
            );
        }
    }
}
