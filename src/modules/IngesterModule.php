<?php

namespace IDG2Migration\modules;

use IDG2Migration\config\GlobalConfig;
use IDG2Migration\config\GlobalConstant;
use IDG2Migration\helpers\ExcelReader;
use Monolog\Handler\StreamHandler;
use IDG2Migration\helpers\DataFilter;
use Monolog\Logger;
use PhpOffice\PhpSpreadsheet\IOFactory;

require_once GlobalConstant::$WP_SETUP;
require_once GlobalConstant::$WP_TAXONOMY_PATH;
require_once GlobalConstant::$WP_POST_PATH;

class IngesterModule
{
    private array $referenceColumns = ['sponsorship','logo','post_author', 'asset_tag', 'publication', 'asset_image_rights'];
    private Logger $logger;
    private string $scriptKey;
    /**
     * @var LoggerModule
     */
    private LoggerModule $loggerModule;
    private array $allWproles;

    /**
     * IngesterModule constructor.
     *
     * @param string $logFileName
     */
    public function __construct($logFileName = '')
    {
        $this->dataFilter = new DataFilter();
        $this->loggerModule = new LoggerModule($logFileName);
        $this->logger = new Logger('migration_logger');
        $logFile = !empty($logFileName) ? trim($logFileName) : 'general';
        $this->logger->pushHandler(
            new StreamHandler(
                $this->dataFilter->getDirectory($logFile).$logFile.time().'.log',
                Logger::INFO
            )
        );
        $this->scriptKey = $logFileName;
    }

    /**
     * @param $sourceDataObject
     * @param $mapItem
     */
    public function termAndTermMetaHandler($sourceDataObject, $mapItem)
    {
        //Log Executed SQL Query before operation start in the log
        $this->logExecutedSqlQuery();

        if (count($sourceDataObject)) {
            $this->logger->info(
                sprintf('Total records lineup for migration {%s}', count($sourceDataObject))
            );
            $noOfRecords = 0;
            $metaColObj = $mapItem['destination']['cols'];
            foreach ($sourceDataObject as $item) {
                $termArray = [];
                $termMetaArray = [];
                foreach ($item as $key => $ite) {
                    if (in_array($key, str_replace('.', '_', $mapItem['source']['cols']))) {
                        $keyIndex = array_search($key, str_replace('.', '_', $mapItem['source']['cols']));
                        if (count($metaColObj[$keyIndex]) > 0) {
                            if ($metaColObj[$keyIndex]['is_meta'] === false) {
                                $termArray[$metaColObj[$keyIndex]['name']] = $ite;
                            } elseif ($metaColObj[$keyIndex]['is_meta'] === true) {
                                if ($metaColObj[$keyIndex]['is_reference']
                                    === true && $metaColObj[$keyIndex]['ref_type']
                                    === '') {
                                    // call function where we have direct taxonomy available for reverse lookup
                                    $termMetaArray[$metaColObj[$keyIndex]['name']] =
                                    $this->taxonomyReferenceRecordHandler($metaColObj, $keyIndex, $ite);
                                } elseif ($metaColObj[$keyIndex]['is_reference'] === true &&
                                $metaColObj[$keyIndex]['ref_type'] === 'content') {
                                    $termMetaArray[$metaColObj[$keyIndex]['name']] =
                                        $termMetaArray[$metaColObj[$keyIndex]['name']] =
                                         $this->postExists(
                                             $metaColObj[$keyIndex]['ref_taxonomy'],
                                             $ite
                                         );
                                } else {
                                    $termMetaArray[$metaColObj[$keyIndex]['name']] = $ite;
                                }
                            } else {
                                $termMetaArray[$metaColObj[$keyIndex]['name']] = $ite;
                            }
                        }
                    }
                }

                $taxonomy = $mapItem['source']['taxonomy'];
                if ($taxonomy == 'manufacturer' || $taxonomy == 'asset_tag' || $taxonomy == 'asset_image_rights') {
                    $termId = $this->addTermDatabyName(
                        $termArray,
                        $mapItem['source']['taxonomy'],
                        $termMetaArray['old_id_in_onecms']
                    );
                } else {
                    $termId = $this->addTermData(
                        $termArray,
                        $mapItem['source']['taxonomy'],
                        $termMetaArray['old_id_in_onecms']
                    );
                }

                if ($termId > 0) {
                    $noOfRecords++;
                    /*
                     * code to handle the case where we are
                     * having the same source field
                     * mapped to multiple destination fields.
                     */
                    if ($this->scriptKey === 'sponsorship') {
                        $termMetaArray = $this->injectInSponsorObject($termArray, $termMetaArray);
                    }
                    $this->addTermMetaData($termMetaArray, $termId);
                    $this->insertInMigrationTermMapping(
                        [
                            'taxonomy' => $taxonomy,
                            'termId' => $termId,
                            'oldIdInOneCms' => $termMetaArray['old_id_in_onecms']
                        ],
                        $this->logger
                    );
                }
                $this->logger->info('----End of migration----------');
            }
            $this->logger->info(
                sprintf('Total records migrated {%s}', $noOfRecords)
            );
        } else {
            $this->logger->info(
                sprintf('Total records lineup for migration {%s}', count($sourceDataObject))
            );
            $this->logger->info('No record to migrate');
        }
    }

    /**
     * @param  $termObject
     * @param  $taxonomy
     * @param  $oneCMSId
     * @return int
     */
    public function addTermData($termObject, $taxonomy, $oneCMSId): int
    {
        $isPresent = $this->termExists($termObject['slug'], $taxonomy);
        if (count($isPresent) === 0) {
            $categoryObject = $this->generateCategoryArray($termObject, $taxonomy, $existingId = 0);
            if (empty($categoryObject['cat_name'])) {
                $this->logger->error(
                    sprintf(' --{%d}-- cat_name is empty. ', $oneCMSId)
                );
                return 0;
            }
            $result = wp_insert_category($categoryObject, true);
            if (is_wp_error($result)) {
                $this->logger->error(
                    sprintf(' --{%d}-- '.$result->get_error_message(), $oneCMSId)
                );
                return 0;
            }
            $this->logger->info(
                sprintf(
                    '----Start migration of {%s} {%s} one cms id {%d}--new id {%d}----------',
                    $taxonomy,
                    $termObject['slug'],
                    $oneCMSId,
                    $result
                )
            );
            $this->logger->info(
                sprintf(
                    '{%d}-{%d} Action {%s} Term -term- {%s} -taxonomy- {%s} -slug- {%s}',
                    $oneCMSId,
                    $result,
                    GlobalConfig::$LOGGER_KEYS['insert'],
                    $termObject['name'],
                    $taxonomy,
                    $termObject['slug']
                )
            );
            $this->logger->info(sprintf('{%d}-{%d} Result {%s}', $oneCMSId, $result, $result));

            return $result;
        } else {
            $categoryObject = $this->generateCategoryArray($termObject, $taxonomy, $isPresent['term_id']);
            if (empty($categoryObject['cat_name'])) {
                $this->logger->error(
                    sprintf(' --{%d}-- cat_name is empty. ', $oneCMSId)
                );
                return 0;
            }
            $result = wp_insert_category($categoryObject, true);
            if (is_wp_error($result)) {
                $this->logger->error(
                    sprintf(' --{%d}-- '.$result->get_error_message(), $oneCMSId)
                );
                return 0;
            }
            $this->logger->info(
                sprintf(
                    '----Start migration of {%s} {%s} one cms id {%d}--new id {%d}----------',
                    $taxonomy,
                    $termObject['slug'],
                    $oneCMSId,
                    $result
                )
            );
            $this->logger->info(
                sprintf(
                    '{%d}-{%d} Action {%s} Term -term- {%s} -taxonomy- {%s} -slug- {%s}',
                    $oneCMSId,
                    $result,
                    GlobalConfig::$LOGGER_KEYS['update'],
                    $termObject['name'],
                    $taxonomy,
                    $termObject['slug']
                )
            );
            $this->logger->info(sprintf('{%d}-{%d} Result {%s}', $oneCMSId, $result, $result));

            return $result;
        }
    }

    /**
     * @param $termMetaObject
     * @param $termId
     */
    public function addTermMetaData($termMetaObject, $termId)
    {
        if (count($termMetaObject) > 0) {
            $oldCMSId = $termMetaObject['old_id_in_onecms'];
            foreach ($termMetaObject as $key => $itm) {
                // TODO: make metaType dynamic
                if (!$this->metadataExists('term', $termId, $key)) {
                    if (!is_array($itm) && strpos($itm, 'ref_error') !== false) {
                        $actualItem = $itm;
                        $itm = '';
                        $result = add_term_meta($termId, $key, $itm, false);
                        $this->logReferenceInfoInLogger($termId, $key, $actualItem, $oldCMSId);
                    } else {
                        $result = add_term_meta($termId, $key, $itm, false);
                        $this->logReferenceInfoInLogger($termId, $key, $itm, $oldCMSId);
                    }
                    // The code is use for Log the value of the array first value
                    if (is_array($itm)) {
                        $itm = $itm[0];
                    }
                    $this->logger->info(
                        sprintf(
                            '{%d}-{%d} Action {%s} termmeta -term id- {%d} -meta key- {%s} -keyvalue- {%s}',
                            $oldCMSId,
                            $termId,
                            GlobalConfig::$LOGGER_KEYS['insert'],
                            $termId,
                            $key,
                            $itm
                        )
                    );
                    $this->logger->info(sprintf('{%d}-{%d} Result {%s}', $oldCMSId, $termId, $result));
                } else {
                    if (!is_array($itm) && strpos($itm, 'ref_error')) {
                        $actualItem = $itm;
                        $itm = '';
                        $result = update_term_meta($termId, $key, $itm, '');
                        $this->logReferenceInfoInLogger($termId, $key, $actualItem, $oldCMSId);
                    } else {
                        $result = update_term_meta($termId, $key, $itm, '');
                        $this->logReferenceInfoInLogger($termId, $key, $itm, $oldCMSId);
                    }
                    // The code is use for Log the value of the array first value
                    if (is_array($itm)) {
                        $itm = $itm[0];
                    }
                    $this->logger->info(
                        sprintf(
                            '{%d}-{%d} Action {%s} termmeta -term id- {%d} -meta key- {%s} -keyvalue- {%s}',
                            $oldCMSId,
                            $termId,
                            GlobalConfig::$LOGGER_KEYS['update'],
                            $termId,
                            $key,
                            $itm
                        )
                    );
                    $this->logger->info(sprintf('{%d}-{%d} Result {%s}', $oldCMSId, $termId, $result));
                }
            }
        }
    }



    /**
     * @param  $termObject
     * @param  $taxonomy
     * @param  $oneCMSId
     * @return int
     */
    public function addTermDatabyName($termObject, $taxonomy, $oneCMSId): int
    {
        $name = $termObject['name'];
        $term_id = $this->getNewReferenceIdByOld($taxonomy, $oneCMSId);
        if ($term_id > 0) {
            $categoryObject = $this->generateCategoryArray($termObject, $taxonomy, $term_id);
        } else {
            $isPresent = $this->termExists($name, $taxonomy);
            $term_id = $isPresent['term_id'];
            if ($term_id > 0) {
                $termObject['slug'] = $this->dataFilter->genrateSlugify($termObject['name']) . "_" .  $oneCMSId;
                $categoryObject = $this->generateCategoryArray($termObject, $taxonomy);
            } else {
                $categoryObject = $this->generateCategoryArray($termObject, $taxonomy);
            }
        }

        $result = wp_insert_category($categoryObject, true);
        $this->logger->info(
            sprintf(
                '----Start migration of {%s} {%s} one cms id {%d}--new id {%d}----------',
                $taxonomy,
                $termObject['slug'],
                $oneCMSId,
                $result
            )
        );
        $this->logger->info(
            sprintf(
                '{%d}-{%d} Action {%s} Term -term- {%s} -taxonomy- {%s} -slug- {%s}',
                $oneCMSId,
                $result,
                GlobalConfig::$LOGGER_KEYS['update'],
                $termObject['name'],
                $taxonomy,
                $termObject['slug']
            )
        );
        $this->logger->info(sprintf('{%d}-{%d} Result {%s}', $oneCMSId, $result, $result));
        return $result;
    }

    /**
     * @param $term
     * @param $taxonomy
     *
     * @return mixed
     */
    public function termExists($term, $taxonomy)
    {
        return term_exists($term, $taxonomy);
    }

    /**
     * @param $metaType
     * @param $objectId
     * @param $metaKey
     *
     * @return bool
     */
    public function metadataExists($metaType, $objectId, $metaKey): bool
    {
        return metadata_exists($metaType, $objectId, $metaKey);
    }

    /**
     * @param  $termObject
     * @param  $taxonomy
     * @param  $existingId
     * @return array
     */
    public function generateCategoryArray($termObject, $taxonomy, $existingId = 0): array
    {
        if (!isset($termObject['name'])) {
            return [];
        } else {
            // default value in is WP 0 for new term
            $categoryObject['cat_ID'] = $existingId;
            // for blank taxonomy return WP default taxonomy which is category
            $categoryObject['taxonomy'] = $taxonomy !== '' ? $taxonomy : 'category';
            $categoryObject['cat_name'] = $termObject['name'];
            $categoryObject['category_description'] =
                $termObject['description'] !== '' ? $termObject['description'] : '';
            $categoryObject['category_nicename'] = $termObject['slug'] !== '' ? $termObject['slug'] : '';
            $categoryObject['category_parent'] = $termObject['parent'] !== '' ? $termObject['parent'] : '';

            return $categoryObject;
        }
    }

    /**
     * @param $termMetaObject
     * @param $termId
     * @param $originalId
     * @param $customKey
     *
     * @return string[]
     */
    public function injectExtraValueInMetaObject($termMetaObject, $termId, $originalId, $customKey): array
    {
        if ($originalId !== '' && $termId !== '') {
            return $termMetaObject + [$customKey => $originalId.'-'.$termId];
        } elseif ($originalId !== '' && $termId === '') {
            return $termMetaObject + [$customKey => $originalId];
        } elseif ($originalId === '' && $termId !== '') {
            return $termMetaObject + [$customKey => $termId];
        }

        return $termMetaObject;
    }

    /**
     * @param $sourceDataObject
     * @param $mapItem
     *
     * @return bool
     */
    public function userAndUserMetaHandler($sourceDataObject, $mapItem)
    {
        //Log Executed SQL Query before operation start in the log
        $this->logExecutedSqlQuery();

        $storyType = 'user';
        $this->allWproles = $this->fetchAllWpRoles();
        $businessUnit = $this->getBuisnessUnit();
        $businessUnit = (array) $businessUnit;
        $totalRecords = count($sourceDataObject);
        $recordsMigrated = 0;

        foreach ($sourceDataObject as $sourceRecord) {
            $data = [];
            $logData = [];
            if (empty($sourceRecord['cms_user_email'])) {
                $logData['error'] = 'email id is empty';
                $logData['user'] = $sourceRecord;
                $this->loggerModule->addInLog($logData);
                continue;
            }

            // for one to one mapping
            foreach ($mapItem['destination']['cols'] as $index => $column) {
                if ($mapItem['destination']['cols'][$index]['is_meta'] === false) {
                    $data[$column['name']] =
                        $sourceRecord[str_replace('.', '_', $mapItem['source']['cols'][$index])] ?? '';
                }
            }

            //for meta and other special cases
            if (count($data) > 0) {
                $userObj = get_user_by('login', $sourceRecord['cms_user_username']);
                $logData['entity'] = $data;
                if (empty($userObj)) {
                    $userId = wp_insert_user($data);
                    if (is_wp_error($userId)) {
                        $logData['error'] = json_encode($userId->errors);
                        $logData['user'] = $sourceRecord;
                        $this->loggerModule->addInLog($logData);
                        continue;
                    }
                    // assign default value, later on add the role if role exists for user
                    //update_user_meta($userId, 'wp_capabilities', '');
                    $userObj = get_user_by('id', $userId);
                    $logData['action'] = GlobalConfig::$INSERT_ACTION;
                } else {
                    $userId = $userObj->ID;
                    $logData['action'] = GlobalConfig::$UPDATE_ACTION;
                }
                $logData['storyType'] = $storyType;
                $logData['newId'] = $userId;
                $logData['oldId'] = $sourceRecord['cms_user_id'];
                $logData['name'] = $sourceRecord['cms_user_username'];
                foreach ($mapItem['destination']['cols'] as $index => $column) {
                    if ($column['is_meta'] === true) {
                        if (($column['has_default']) === true) {
                            update_user_meta($userId, $column['name'], $businessUnit);
                            $logData['meta'][$column['name']] = $businessUnit;
                        } elseif (($column['is_social_media']) === true) {
                            $socialMedia = $this->assignUserSocialMedia($userId, $sourceRecord['social_network']);
                            $logData['meta']['social_media'] = $socialMedia;
                        } elseif (in_array($column['name'], ['old_id_in_onecms', 'person_id'])) {
                            // if old id is already migrated than do not migrate
                            // again, migrate for first time only
                            $oldId = get_user_meta($userId, $column['name'], true);
                            if (empty($oldId)) {
                                update_user_meta(
                                    $userId,
                                    $column['name'],
                                    $sourceRecord[str_replace('.', '_', $mapItem['source']['cols'][$index])]
                                );
                            }
                            $logData['meta'][$column['name']] =
                            $sourceRecord[str_replace('.', '_', $mapItem['source']['cols'][$index])];
                        } elseif ($column['name'] === 'wp_capabilities') {
                            if (!empty($sourceRecord['user_role'])) {
                                $allRoles = $this->assignUserRole($userObj, $sourceRecord['user_role']);
                                $logData['meta'][$column['name']] =
                                ['oldRole' => $sourceRecord['user_role'], 'newRole' => json_encode($allRoles)];
                            }
                        } elseif ($column['name'] === 'profile-photo') {
                            $imgId = $this->postExists(
                                $column['ref_taxonomy'],
                                $sourceRecord['person_image_id']
                            );
                            $profilePhoto = [];
                            if ($imgId > 0) {
                                $profilePhoto = array(
                                    'media_id' => $imgId,
                                    'full' => wp_get_attachment_url($imgId)
                                );
                                update_user_meta(
                                    $userId,
                                    $column['name'],
                                    $profilePhoto
                                );
                            }
                            $logData['reference'][$column['name']] = array(
                                'newId' => $imgId,
                                'oldId' => $sourceRecord['person_image_id']
                            );
                            $logData['meta'][$column['name']] = $imgId > 0 ? $profilePhoto : 0;
                        } else {
                            update_user_meta(
                                $userId,
                                $column['name'],
                                $sourceRecord[str_replace('.', '_', $mapItem['source']['cols'][$index])]
                            );
                            $logData['meta'][$column['name']] =
                                $sourceRecord[str_replace('.', '_', $mapItem['source']['cols'][$index])];
                        }
                    }
                }
            }
            ++$recordsMigrated;
            $this->loggerModule->addInLog($logData);
        }

        $this->loggerModule->addCountInLog(['total' => $totalRecords, 'migrated' => $recordsMigrated]);

        return true;
    }

    /**
     * @return int
     */
    public function getBuisnessUnit()
    {
        global $wpdb;
        $termId = '';

        $sql = 'SELECT wp_terms.term_id as tid FROM wp_termmeta
               inner join wp_terms on wp_terms.term_id = wp_termmeta.term_id
               WHERE meta_key = "publication_type"
               and meta_value = "business-unit"
               order by wp_terms.term_id ASC
               limit 1';

        $results = $wpdb->get_results($sql);

        if (count($results) > 0) {
            $termId = $results[0]->tid;
        }

        return $termId;
    }

    /**
     * @param $userId
     * @param $socialNetwork
     *
     * @return array
     */
    public function assignUserSocialMedia($userId, $socialNetwork)
    {
        $allSocialMedia = [];
        $dataArray = explode(',', $socialNetwork);
        foreach ($dataArray as $value) {
            $socialNetworkSingle = explode('|', $value);
            update_user_meta(
                $userId,
                GlobalConfig::$SOCIAL_NETOWRK[$socialNetworkSingle[0]],
                $socialNetworkSingle[1] ?? ''
            );
            $allSocialMedia[GlobalConfig::$SOCIAL_NETOWRK[$socialNetworkSingle[0]]] = $socialNetworkSingle[1] ?? '';
        }

        return $allSocialMedia;
    }

    /**
     * @param $userObject
     * @param $userRoles
     * @return array
     */
    public function assignUserRole($userObject, $userRoles)
    {
        //$userRoles = 'IDG Staff Editor,SuperAdmins,IDG SMS Adops'; // Testing
        //if ($userObject->ID === 28) {
        //  $userRoles = 'IDG Staff Editor'; // Testing
        //}

        $allRoles = [];
        $userCurrentRoles = $userObject ? $userObject->roles : [];
        $sourceUserRoles = explode(',', $userRoles);

        if ($userObject->ID === 1) {
            return;
        }

        // remove roles of current user
        if (count($userCurrentRoles) > 0) {
            foreach ($userCurrentRoles as $userRole) {
                $userObject->remove_role($userRole);
            }
        }

        // add new roles
        foreach ($sourceUserRoles as $userRole) {
            if (!empty($this->allWproles[GlobalConfig::$USER_ROLES[$userRole]])) {
                $userObject->add_role($this->allWproles[GlobalConfig::$USER_ROLES[$userRole]]);
                $allRoles[] = $this->allWproles[GlobalConfig::$USER_ROLES[$userRole]];
            }
        }

        return $allRoles;
    }

    /**
     * @param $business
     * @param $publication
     */
    public function setBusinessAndPublicationData($business, $publication)
    {
        $this->logger->info(
            sprintf('Total records lineup for migration {%s}', 2)
        );
        $businessId = $this->addTermData($business['term'], $business['taxonomy'], 0);
        $noOfRecords = 0;
        if (!is_wp_error($businessId)) {
            $noOfRecords++;
            if (empty($business['meta']['publication_type'])) {
                $business['meta']['publication_type'] = 'business-unit';
            } else {
                $business['meta']['publication_type'] = 'publication';
            }
            $this->addTermMetaData($business['meta'], $businessId, '');

            $this->logger->info('----End of migration----------');

            $publication['term']['parent'] = $businessId;
            $publicationId = $this->addTermData($publication['term'], $publication['taxonomy'], 0);

            if (!is_wp_error($publicationId)) {
                $noOfRecords++;
                if (empty($publication['meta']['publication_type'])) {
                    $publication['meta']['publication_type'] = 'business-unit';
                } else {
                    $publication['meta']['publication_type'] = 'publication';
                }
                $this->addTermMetaData($publication['meta'], $publicationId, '');
            }
        }
        $this->logger->info('----End of migration----------');
        $this->logger->info(
            sprintf('Total records migrated {%s}', $noOfRecords)
        );
        echo "Records has been successfully migrated".PHP_EOL;
    }



    /**
     * Function to insert the Product VC code
     * @param $productvc array
     */
    public function setProductVcMigrationData($productvc)
    {
        $this->logger->info(
            sprintf('Total records lineup for migration {%s}', 1)
        );
        $productvcId = $this->addTermData(
            $productvc['term'],
            $productvc['taxonomy'],
            2
        );
        if (!is_wp_error($productvcId)) {
            $this->addTermMetaData($productvc['meta'], $productvcId, '');
            $this->logger->info('----End of migration----------');
            $this->logger->info(
                sprintf('Total records migrated {%s}', 1)
            );
            echo "Records has been successfully migrated" . PHP_EOL;
        } else {
            echo "Error: Records has not been migrated" . PHP_EOL;
        }
    }

    public function logReferenceInfoInLogger($termId, $key, $itm, $oldCMSId)
    {
        if (in_array($key, $this->referenceColumns)) {
            if (strpos($itm, 'ref_error') !== false) {
                $errArray = explode('|', $itm);
                $this->logger->error(
                    sprintf(
                        '{%d}-{%d} Reference NOT found {%s} with id {%d}',
                        $oldCMSId,
                        $termId,
                        $key,
                        $errArray[1]
                    )
                );
            } else {
                $this->logger->info(
                    sprintf(
                        '{%s}-{%d} Reference of {%s} change to {%s} now ',
                        $oldCMSId,
                        $termId,
                        $key,
                        $itm
                    )
                );
            }
        }
    }

    /**
     * @param $ref_taxonomy
     * @param $value
     *
     * @return mixed
     */
    public function getNewReferenceIdByOld($ref_taxonomy, $value, $meta_key = 'old_id_in_onecms')
    {
        global $wpdb;
        if (!empty($value)) {
            if ($meta_key === 'old_id_in_onecms') {
                $results = $wpdb->get_results(
                    $wpdb->prepare(
                        'SELECT term_id
                            FROM migration_term_mapping
                            WHERE
                            old_id_in_onecms = %d
                            AND taxonomy_type = %s',
                        $value,
                        $ref_taxonomy
                    )
                );
            } else {
                $sql = 'select tm.term_id from '.$wpdb->prefix.'termmeta as tm, '.$wpdb->prefix.'term_taxonomy as tt
                        where
                        tm.meta_key = "'.$meta_key.'"
                        AND tm.meta_value = '.$value.'
                        AND tm.term_id = tt.term_id
                        AND tt.taxonomy = "'.$ref_taxonomy.'" ';
                $results = $wpdb->get_results(
                    $sql
                );
            }
            if (count($results) > 0) {
                return $results[0]->term_id;
            } else {
                return 'ref_error';
            }
        } else {
            return 0;
        }
    }

    /**
     * @param $sourceDataObject
     * @param $mapItem
     */
    public function categoryAndMetaHandler($sourceDataObject, $mapItem)
    {
        //Log Executed SQL Query before operation start in the log
        $this->logExecutedSqlQuery();

        if (!empty($mapItem['excelLog'])) {
            $this->addExcelLog($mapItem['excelLog']);
        }

        if (count($sourceDataObject)) {
            $noOfRecords = 0;
            $this->logger->info(
                sprintf('Total records lineup for migration {%s}', count($sourceDataObject))
            );
            foreach ($sourceDataObject as $source) {
                $taxonomy = $mapItem['source']['taxonomy'];
                $inputArray = [];
                $termId = $mapItem['termsData'][$source['category_golden_map_golden_id']];
                if (!empty($termId)) {
                    foreach ($mapItem['source']['cols'] as $key => $cols) {
                        if ($mapItem['destination']['cols'][$key]['is_meta'] === false
                            &&
                            $mapItem['destination']['cols'][$key]['name'] != 'name') {
                            $inputArray[$mapItem['destination']['cols'][$key]['name']] =
                            $source[str_replace('.', '_', $cols)];
                        }
                    }

                    $result = wp_update_term($termId, $taxonomy, $inputArray);

                    if (is_array($result) && !empty($result['term_id'])) {
                        $this->insertInMigrationTermMapping(
                            [
                                'taxonomy' => $taxonomy,
                                'termId' => $termId,
                                'oldIdInOneCms' => $source['category_id']
                            ],
                            $this->logger
                        );
                        ++$noOfRecords;
                        foreach ($mapItem['source']['cols'] as $key => $cols) {
                            if ($mapItem['destination']['cols'][$key]['is_meta'] === true
                                &&
                                $mapItem['destination']['cols'][$key]['name'] != 'golden_id') {
                                $metaResult = update_term_meta(
                                    $termId,
                                    $mapItem['destination']['cols'][$key]['name'],
                                    $source[str_replace('.', '_', $cols)]
                                );
                            }

                            $inputArray[$mapItem['destination']['cols'][$key]['name']] =
                            $source[str_replace('.', '_', $cols)];
                        }

                        $this->logger->info(
                            sprintf(
                                '----Start migration of {%s} {%s} one cms id {%d}--new id {%d}----------',
                                $taxonomy,
                                $source['category_slug'],
                                $source['category_id'],
                                is_object($result) ? json_encode($result->errors) : $result['term_id'],
                            )
                        );

                        $this->logger->info(
                            sprintf(
                                '{%d}-{%d} Action {%s} Term -taxonomy- {%s} -slug- {%s}',
                                $source['category_id'],
                                is_object($result) ? json_encode($result->errors) : $result['term_id'],
                                GlobalConfig::$LOGGER_KEYS['update'],
                                $taxonomy,
                                $source['category_slug']
                            )
                        );

                        $this->logger->info(
                            sprintf(
                                '{%d}-{%d} values - {%s}',
                                $source['category_id'],
                                is_object($result) ? json_encode($result->errors) : $result['term_id'],
                                json_encode($inputArray)
                            )
                        );

                        $this->logger->info(
                            sprintf(
                                '{%d}-{%d} Result {%s}',
                                $source['category_id'],
                                (is_object($result) ? json_encode($result->errors) : $result['term_id']),
                                (is_object($result) ? json_encode($result->errors) : $result['term_id'])
                            )
                        );

                        $this->logger->info(
                            sprintf(
                                '----End migration of {%s} {%s} one cms id {%d}--new id {%d}----------',
                                $taxonomy,
                                $source['category_slug'],
                                $source['category_id'],
                                is_object($result) ? json_encode($result->errors) : $result['term_id'],
                            )
                        );
                    } else {
                        $this->logger->error(
                            sprintf(
                                '{%d}-{%d} category_id - {%d} golden_id - {%d} Error - {%s}',
                                $source['category_id'],
                                (is_object($result) ? json_encode($result->errors) : $result['term_id']),
                                $source['category_id'],
                                $source['category_golden_map_golden_id'],
                                (is_object($result) ? json_encode($result->errors) : $result['term_id'])
                            )
                        );
                    }
                }
            }

            $this->logger->info('----End of migration----------');

            $this->logger->info(
                sprintf('Total records migrated {%s}', $noOfRecords)
            );
        }
    }

    /**
     * @param mixed $excelLog
     *
     * @return bool
     */
    public function addExcelLog($excelLog)
    {
        $this->logger->info(
            sprintf('---Excel Parsing Result Start---')
        );

        $this->logger->info(
            sprintf('%s', json_encode($excelLog))
        );

        $this->logger->info(
            sprintf('---Excel Parsing Result End---')
        );

        return true;
    }

    /**
     * @return array
     */
    public function parseExcelAndStoreCategory()
    {
        ini_set('memory_limit', '1024M');
        $idsArray = [];
        $logArray = [];
        $inputFileType = 'Xlsx';
        $inputFileName = GlobalConstant::$INPUT_DIR.'category.xlsx';
        $sheetname = 'New GT with IDs';
        $filterSubset = new ExcelReader(1, 1001, range('A', 'F')); // set no of records in excel
        $reader = IOFactory::createReader($inputFileType);
        $reader->setLoadSheetsOnly($sheetname);
        $reader->setReadFilter($filterSubset);
        $spreadsheet = $reader->load($inputFileName);
        $columns = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

        foreach ($columns as $key => $assocValue) {
            if ($key === 1 || in_array($assocValue['A'], [1356, 1390])) {
                continue;
            }

            $value = array_values($assocValue);
            $result = $this->checkLevelMissing($value);
            if ($result) {
                // level missing
                $filterArray = array_filter($value);
                $logArray[] = [
                'golden_id' => $filterArray[0],
                'message' => 'incorrect hierarchy found', ];
            } else {
                $parentKey = 0;
                $filterArray = array_filter($value);
                $onlyKeys = array_keys($filterArray);
                $lastIndex = end($onlyKeys);
                $termLable = end($filterArray);

                if ((count($filterArray) - $lastIndex === 1)) {
                    $parent = $filterArray[count($filterArray) - 2];
                    $parentKey = array_search($parent, $idsArray);
                }

                //$term = term_exists($termLable, 'category', $parentKey);

                $term = $this->getNewReferenceIdByOld('category', $filterArray[0], 'golden_id');

                if ($term == 'ref_error') {
                    $term = null;
                }

                if ((count($filterArray) - $lastIndex === 1) && $term === null) {
                    $parent = $filterArray[count($filterArray) - 2];
                    $parentKey = array_search($parent, $idsArray);
                    if ($parentKey) {
                        $term = wp_insert_term($termLable, 'category', ['slug' => '', 'parent' => $parentKey]);
                    } else {
                        $term = wp_insert_term($termLable, 'category', ['slug' => '']);
                    }

                    if (is_wp_error($term)) {
                        $logArray[] = [
                        'golden_id' => $filterArray[0],
                        'message' => $term->get_error_message(), ];
                    } else {
                        // if new category is inserted
                        if (!empty($term['term_id'])) {
                            $createdId = $term['term_id'];
                            update_term_meta($createdId, 'golden_id', $filterArray[0]);
                            $logArray[] = ['action' => GlobalConfig::$LOGGER_KEYS['insert'],
                            'golden_id' => $filterArray[0],
                            'category_id' => $createdId, ];
                            $idsArray[$createdId] = $termLable;
                        } elseif (!empty($term['error_data']['term_exists'])) {
                            // if category already present
                            update_term_meta($term['error_data']['term_exists'], 'golden_id', $filterArray[0]);
                            $logArray[] = ['action' => GlobalConfig::$LOGGER_KEYS['update'],
                            'golden_id' => $filterArray[0],
                            'category_id' => $createdId, ];
                        }
                    }
                } else {
                    $update = wp_update_term($term, 'category', [
                        'name' => $termLable,
                    ]);
                    $logArray[] = [
                        'golden_id' => $filterArray[0],
                        'term_id' => $term,
                        'message' => 'term updated for '.$termLable, ];
                }
            }
        }

        return $logArray;
    }

    /**
     * @param mixed $data
     *
     * @return bool
     */
    public function checkLevelMissing($data)
    {
        $prevElement = '';
        $found = false;

        foreach ($data as $key => $value) {
            if (empty($prevElement) && !empty($value) && $key != 0) {
                $found = true;
                break;
            }
            $prevElement = $value;
        }

        return $found;
    }

    /**
     * @return array
     */
    public function fetchAllWpRoles()
    {
        global $wp_roles;
        $allRoles = [];

        foreach ($wp_roles->roles as $key => $value) {
            $allRoles[$value['name']] = $key;
        }

        return $allRoles;
    }
    /**
     * @param $sourceDataObject
     * @param $mapItem
     */
    public function postAndPostMetaHandler($sourceDataObject, $mapItem)
    {
        //Log Executed SQL Query before operation start in the log
        $this->logExecutedSqlQuery();

        if (count($sourceDataObject)) {
            $this->logger->info(
                sprintf('Total records lineup for migration {%s}', count($sourceDataObject))
            );
            $noOfRecords = 0;
            $metaColObj = $mapItem['destination']['cols'];
            foreach ($sourceDataObject as $item) {
                $postArray = [];
                $postMetaArray = [];
                $postTerm = [];
                foreach ($item as $key => $ite) {
                    $is_key_present = in_array($key, str_replace('.', '_', $mapItem['source']['cols']));
                    if (!$is_key_present) {
                        // special case
                        if (array_key_exists($key, GlobalConfig::$ALISE_SPECIAL_KEYS)) {
                            $is_key_present = true;
                        }
                    }
                    if ($is_key_present) {
                        $keyIndex = array_search($key, str_replace('.', '_', $mapItem['source']['cols']));
                        if (!$keyIndex) {
                            $keyIndex = array_search(GlobalConfig::$ALISE_SPECIAL_KEYS[$key], $mapItem['source']['cols']);
                        }
                        if (count($metaColObj[$keyIndex]) > 0) {
                            if ($metaColObj[$keyIndex]['is_meta'] === false) {
                                if ($metaColObj[$keyIndex]['is_reference'] === true && $metaColObj[$keyIndex]['ref_type'] === 'user') {
                                    $postArray[$metaColObj[$keyIndex]['name']] = $this->getUserByOldId($ite);
                                } else {
                                    $postArray[$metaColObj[$keyIndex]['name']] = $ite;
                                }
                            } elseif ($metaColObj[$keyIndex]['is_meta'] === true) {
                                if ($metaColObj[$keyIndex]['is_reference'] === true && $metaColObj[$keyIndex]['ref_type'] === '') {
                                    // get new system's reference based on old id
                                    $newTermId = array();
                                    if (strpos($ite, ',') !== false) {
                                        $termsID = explode(',', $ite);
                                        foreach ($termsID as $termKey => $termVal) {
                                            $refId = $this->taxonomyReferenceRecordHandler(
                                                $metaColObj,
                                                $keyIndex,
                                                $termVal
                                            );
                                            $newTermId[] = (int)$refId;
                                        }
                                        $postTerm[] = array(
                                            'termID' => $newTermId,
                                            'taxonomy' => $metaColObj[$keyIndex]['ref_taxonomy'],
                                            'old_in_one_cms_id' => $ite
                                        );
                                    } else {
                                        $refId = $this->taxonomyReferenceRecordHandler(
                                            $metaColObj,
                                            $keyIndex,
                                            $ite
                                        );
                                        $postTerm[] = array(
                                            'termID' => array((int)$refId),
                                            'taxonomy' => $metaColObj[$keyIndex]['ref_taxonomy'],
                                            'old_in_one_cms_id' => $ite
                                        );
                                    }
                                } else {
                                    $postMetaArray[$metaColObj[$keyIndex]['name']] = $ite;
                                }
                            }
                        }
                    }
                }
                $postArray['post_type'] = $mapItem['destination']['content_type'] !== '' ? $mapItem['destination']['content_type'] : 'post';
                $postID = $this->addPostData(
                    $postArray,
                    $postMetaArray['old_id_in_onecms']
                );
                if ($postID !== 0) {
                    if ($postArray['post_type'] === 'attachment') {
                        $postMetaArray = $this->injectExtraValueInMetaObject(
                            $postMetaArray,
                            1,
                            '',
                            'active'
                        );
                    }
                    foreach ($postTerm as $termKey => $termValue) {
                        $termId = $this->addPostTerm(
                            $postID,
                            $termValue['termID'],
                            $termValue['taxonomy'],
                            $termValue['old_in_one_cms_id']
                        );
                    }
                    $this->addPostMetaData($postMetaArray, $postID);
                    $noOfRecords++;
                    $this->logger->info('----End of migration----------');
                }
            }
            $this->logger->info(
                sprintf('Total records migrated {%s}', $noOfRecords)
            );
        }
    }

    /**
     * @param $postObject
     * @param $oneCMSId
     * @return int
     */
    public function addPostData($postObject, $oneCMSId): int
    {
        $isTitleSanitize = false;
        if ((trim($postObject['post_title']) === '' || is_null($postObject['post_title']))&& $postObject['post_type'] === 'attachment') {
            $title = explode('/', $postObject['guid']);
            $postObject['post_title'] = sanitize_title(end($title));
            $isTitleSanitize = true;
        }
        $isPresent = $this->postExists($postObject['post_type'], $oneCMSId, $postObject['post_title']);
        if ($isPresent === 0) {
            $result = wp_insert_post($postObject, true);
            if (is_wp_error($result)) {
                $this->logger->error(
                    sprintf(' --{%d}-- '.$result->get_error_message(), $oneCMSId)
                );
                return 0;
            }
            $this->logger->info(
                sprintf(
                    '----Start migration of {%s} {%s} one cms id {%d}--new id {%d}----------',
                    $postObject['post_type'],
                    $postObject['post_title'],
                    $oneCMSId,
                    $result
                )
            );
            if ($isTitleSanitize) {
                $this->logger->info(
                    sprintf(
                        '----{%d}-{%d} Post title is null/blank hence updated by file name {%s}.----',
                        $oneCMSId,
                        $result,
                        $postObject['post_title'],
                    )
                );
            }
            $this->logger->info(
                sprintf(
                    '{%d}-{%d} Action {%s} -%s- {%s}',
                    $oneCMSId,
                    $result,
                    GlobalConfig::$LOGGER_KEYS['insert'],
                    $postObject['post_type'],
                    $postObject['post_title'],
                )
            );
            $this->logReferenceInfoInLogger($result, 'post_author', $postObject['post_author'], $oneCMSId);
            return $result;
        } else {
            $postObject['ID'] = $isPresent;
            $result = wp_update_post($postObject, true);
            $this->logger->info(
                sprintf(
                    '----Start migration of {%s} {%s} one cms id {%d}--new id {%d}----------',
                    $postObject['post_type'],
                    $postObject['post_title'],
                    $oneCMSId,
                    $result
                )
            );
            if ($isTitleSanitize) {
                $this->logger->info(
                    sprintf(
                        '----{%d}-{%d} Post title is null/blank hence updated by file name {%s}.----',
                        $oneCMSId,
                        $result,
                        $postObject['post_title'],
                    )
                );
            }
            $this->logger->info(
                sprintf(
                    '{%d}-{%d} Action {%s} -%s- {%s}',
                    $oneCMSId,
                    $result,
                    GlobalConfig::$LOGGER_KEYS['update'],
                    $postObject['post_type'],
                    $postObject['post_title'],
                )
            );
            $this->logReferenceInfoInLogger($result, 'post_author', $postObject['post_author'], $oneCMSId);
            return $result;
        }
    }

    /**
     * @param $postMeta
     * @param $postId
     */
    public function addPostMetaData($postMeta, $postId)
    {
        if (count($postMeta) > 0) {
            $oldCMSId = $postMeta['old_id_in_onecms'];
            foreach ($postMeta as $key => $itm) {
                // TODO: make metaType dynamic

                if (!$this->metadataExists('post', $postId, $key)) {
                    if (!is_array($itm) && strpos($itm, 'ref_error') !== false) {
                        $actualItem = $itm;
                        $itm = '';
                        $result = add_post_meta($postId, $key, $itm, false);
                        $this->logReferenceInfoInLogger($postId, $key, $actualItem, $oldCMSId);
                    } else {
                        $result = add_post_meta($postId, $key, $itm, false);
                        $this->logReferenceInfoInLogger($postId, $key, $itm, $oldCMSId);
                    }
                    // The code is use for Log the value of the array first value
                    if (is_array($itm)) {
                        $itm = $itm[0];
                    }
                    $this->logger->info(
                        sprintf(
                            '{%d}-{%d} Action {%s} postmeta -post id- {%d} -meta key- {%s} -keyvalue- {%s}',
                            $oldCMSId,
                            $postId,
                            GlobalConfig::$LOGGER_KEYS['insert'],
                            $postId,
                            $key,
                            $itm
                        )
                    );
                } else {
                    if (!is_array($itm) && strpos($itm, 'ref_error') !== false) {
                        $actualItem = $itm;
                        $itm = '';
                        $result = update_post_meta($postId, $key, $itm, '');
                        $this->logReferenceInfoInLogger($postId, $key, $actualItem, $oldCMSId);
                    } else {
                        $result = update_post_meta($postId, $key, $itm, '');
                        $this->logReferenceInfoInLogger($postId, $key, $itm, $oldCMSId);
                    }
                    // The code is use for Log the value of the array first value
                    if (is_array($itm)) {
                        $itm = $itm[0];
                    }
                    $this->logger->info(
                        sprintf(
                            '{%d}-{%d} Action {%s} postmeta -post id- {%d} -meta key- {%s} -keyvalue- {%s}',
                            $oldCMSId,
                            $postId,
                            GlobalConfig::$LOGGER_KEYS['update'],
                            $postId,
                            $key,
                            $itm
                        )
                    );
                }
            }
        }
    }

    /**
     * @param $postType
     * @param $oneCMSId
     * @param $postTitle
     * @return int
     */
    public function postExists($postType, $oneCMSId, $postTitle = ''): int
    {
        $args = array(
            'post_type' => $postType,
            'post_per_page' => 1,
            'meta_query' => array(
                array(
                    'key' => 'old_id_in_onecms',
                    'value' => $oneCMSId,
                    'compare' => '=',
                )
            )
        );
        $postData = get_posts($args);
        if (count($postData) > 0) {
            return $postData[0]->ID;
        }
        return 0;
    }

    /**
    * @param $oneCMSId
    * @return int
    */
    public function getUserByOldId($oneCMSId): int
    {
        if ($oneCMSId > 0) {
            $args = array(
                'meta_key' => 'old_id_in_onecms',
                'meta_value' => $oneCMSId
            );
            $userData =  get_users($args);
            if (count($userData) > 0) {
                return $userData[0]->ID;
            }
        }
        return 0;
    }

    /**
     * Function is use for taxonomy to taxonomy reference lookup
     * @param $metaColObj
     * @param $keyIndex
     * @param $ite
     * @return int|mixed|string
     */
    public function taxonomyReferenceRecordHandler($metaColObj, $keyIndex, $ite)
    {
        // get new system's reference based on old id
        $referenceId = $this->getNewReferenceIdByOld(
            $metaColObj[$keyIndex]['ref_taxonomy'],
            $ite,
        );
        if ($referenceId === 'ref_error') {
            return $referenceId."|".$ite;
        } elseif ($referenceId === 0) {
            return '';
        } else {
            return $referenceId;
        }
    }

    /**
     * @return int
     */
    public function getPublication()
    {
        global $wpdb;
        $termId = '';

        $sql = 'SELECT wp_terms.term_id as tid FROM wp_termmeta
               inner join wp_terms on wp_terms.term_id = wp_termmeta.term_id
               WHERE meta_key = "publication_type"
               and meta_value = "publication"
               order by wp_terms.term_id ASC
               limit 1';

        $results = $wpdb->get_results($sql);

        if (count($results) > 0) {
            $termId = $results[0]->tid;
        }

        return $termId;
    }

    /*
     *@param $termMetaArray
     *@param $termArray
     */
    public function injectInSponsorObject($termArray, $termMetaArray):array
    {
        $termMetaArray = $this->injectExtraValueInMetaObject(
            $termMetaArray,
            $termArray['name'],
            '',
            'display_name'
        );
        $termMetaArray = $this->injectExtraValueInMetaObject(
            $termMetaArray,
            $this->getBuisnessUnit(),
            '',
            'business_unit'
        );
        $termMetaArray = $this->injectExtraValueInMetaObject(
            $termMetaArray,
            (array)$this->getPublication(),
            '',
            'publication'
        );
        return $termMetaArray;
    }

    /**
     * @param $postID
     * @param $termId
     * @param $termTaxonomy
     * @param $oldCMSId
     */
    public function addPostTerm($postID, $termId, $termTaxonomy, $oldCMSId)
    {
        $taxonomy_ids = wp_set_post_terms($postID, $termId, $termTaxonomy);
        if (!is_wp_error($taxonomy_ids)) {
            $this->logReferenceInfoInLogger(
                $postID,
                $termTaxonomy,
                implode(',', $taxonomy_ids),
                $oldCMSId
            );
            return $taxonomy_ids;
        } else {
            $this->logReferenceInfoInLogger(
                $postID,
                $termTaxonomy,
                0,
                $oldCMSId
            );
            return 0;
        }
    }

    /**
     * Log Executed SQL Query
     * @param mixed
     */
    public function logExecutedSqlQuery()
    {
        if (isset($_SESSION['executed_sql'])) {
            $executed_sql = $_SESSION['executed_sql'];
            $this->logger->info(
                sprintf('SQL Query Executed :{%s}', $executed_sql)
            );
            unset($_SESSION['executed_sql']);
        }
    }


    /**
     * Migration for add us territory taxonomy.
     * @param $usTermObject array
     */
    public function insertUsTerritory($usTermObject)
    {
        global $wpdb;
        if (!empty($usTermObject)) {
            $this->logger->info(
                sprintf('Total records lineup for migration {%s}', 1)
            );
            $isPresent = $this->termExists(
                $usTermObject['term']['name'],
                $usTermObject['taxonomy']
            );
            $term_id = $isPresent['term_id'];
            if (!$term_id) {
                $this->logger->info(
                    sprintf(
                        '----Start migration of {%s} {%s} one cms id {%d}--new id {%d}----------',
                        $usTermObject['taxonomy'],
                        $usTermObject['slug'],
                        $usTermObject['meta']['old_id_in_onecms'],
                        $term_id
                    )
                );
                //Insert into wp_term table
                $insert_us_term_query = 'insert into ' . $wpdb->prefix . 'terms
                set name = "' . $usTermObject['term']['name'] . '",
                slug = "' . $usTermObject['term']['slug'] . '"';
                $wpdb->get_results($insert_us_term_query);
                $term_id = $wpdb->insert_id;
                if ($term_id) {
                    //Insert into wp_term_taxonomy table
                    $insert_us_term_query = 'insert into ' .
                    $wpdb->prefix . 'term_taxonomy
                    set term_id = ' . $term_id . ',
                    taxonomy = "' . $usTermObject['taxonomy'] . '",
                    description = ""';
                    $wpdb->get_results($insert_us_term_query);
                } else {
                    $result['error'] =  'US data not inserted on Term table';
                    $this->logger->error(
                        sprintf($result['error'])
                    );
                }
            } else {
                $this->logger->info(
                    sprintf(
                        "{%s} - {%s} Data is already presented on term table.",
                        $usTermObject['meta']['old_id_in_onecms'],
                        $term_id
                    )
                );
            }

            //Insert into wp_termmeta table
            if ($term_id > 0) {
                $this->addTermMetaData(
                    $usTermObject['meta'],
                    $term_id,
                    $isPresent['term_id']
                );
                $this->logger->info('----End of migration----------');
                $this->logger->info(
                    sprintf('Total records migrated {%s}', 1)
                );
            }
        }
        echo "Script executed successfully" . PHP_EOL;
    }

    /**
     * @param $origin
     */
    public function setOriginCMSData($origin)
    {
        $this->logger->info(
            sprintf('Total records lineup for migration {%s}', 1)
        );
        $originCMSId = $this->addTermData($origin['term'], $origin['taxonomy'], 0);
        $noOfRecords = 0;
        if (!is_wp_error($originCMSId)) {
            $noOfRecords++;
        }
        $this->logger->info('----End of migration----------');
        $this->logger->info(
            sprintf('Total records migrated {%s}', $noOfRecords)
        );
        echo "Records has been successfully migrated".PHP_EOL;
    }

    /**
     * @param array $data
     * @param Logger $logger
     *
     */
    public function insertInMigrationTermMapping($data, Logger $logger)
    {
        global $wpdb;
        if (!empty($data['taxonomy']) && !empty($data['termId']) && !empty($data['oldIdInOneCms'])) {
            $results = $wpdb->get_results(
                $wpdb->prepare(
                    'SELECT term_id
                        FROM migration_term_mapping
                        WHERE
                        taxonomy_type = %s AND
                        term_id = %d AND
                        old_id_in_onecms = %d',
                    $data['taxonomy'],
                    $data['termId'],
                    $data['oldIdInOneCms']
                )
            );

            if (empty($results)) {
                $wpdb->insert(
                    'migration_term_mapping',
                    [
                        'taxonomy_type' => $data['taxonomy'],
                        'term_id' => $data['termId'],
                        'old_id_in_onecms' => $data['oldIdInOneCms']
                    ],
                    ['%s', '%d', '%d']
                );
                $data['newId'] = $wpdb->insert_id ?? '';
                $logger->info(
                    sprintf('insertion in migration_term_mapping table done - {%s}', json_encode($data))
                );
            } else {
                $logger->info(
                    sprintf('record not inserted as details already exists in migration_term_mapping table - {%s}', json_encode($data))
                );
            }
        } else {
            $logger->error(
                sprintf('insertion in migration_term_mapping failed. invalid input parameters - {%s}', json_encode($data))
            );
        }

        return;
    }
}
