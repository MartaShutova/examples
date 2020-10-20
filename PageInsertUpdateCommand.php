<?php

namespace ContentCustomBundle\Command\sqs;

use ContentCustomBundle\Command\BaseConsumerCommand;
use Sulu\Component\Content\Form\Exception\InvalidFormException;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\HttpFoundation\Request;


class PageInsertUpdateCommand extends BaseConsumerCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('page:insert:update')
            ->setDescription('Insert or update pages from json file')
            ->addArgument('queue', null, InputOption::VALUE_REQUIRED, 'edology-course-parser');
    }

    public function processMessage($message)
    {
        $message = current($message);
        $m = json_decode($message['Body'],true);
        $records = isset($m['Records']) ? $m['Records'] : null;

        if ($records) {
            foreach ($records as $record) {
                $bucket = ($record['s3']['bucket']['name']);
                $key = urldecode($record['s3']['object']['key']);

                $container = $this->getContainer();
                $json = $container->get('ia.s3')->getFile($key, $bucket);
                $language = 'en';
                $action = 'draft';
                $type = 'page';

                $objects = json_decode($json, true);
                $fieldsArray = ['title', 'tag', 'maint_text', 'entry_requirements', 'syllabs', 'start_dates', 'duration', 'award', 'fees', 'url','specialisations',
                    'brochure_url', 'employability', 'fees_block', 'header_text', 'text_block', 'uk_eu_fees', 'international_fees','fees_and_funding','campus'];
                $textFields = ['maint_text', 'entry_requirements', 'syllabs', 'fees_block', 'text_block', 'careers'];
                foreach ($objects as $object) {
                    if (isset($object['title']) && $object['title'] != '' && $object['title'] != null) {
                        $data = [];
                        foreach ($fieldsArray as $field) {
                            !empty($object[$field]) ? $data[$field] = $object[$field] : $data[$field] = '';
                        }
                        if (!empty($object['careers']) && is_array($object['careers'])) {
                            foreach ($object['careers'] as $obj) {
                                $data['careers'] .= $obj;
                            }
                        } else {
                            $data['careers'] = '';
                        }
                        foreach ($textFields as $field) {
                            if ($data[$field] != '' && strpos($data[$field], '<') == false && strpos($data[$field], '>') == false) $data[$field] = '<div>' . $data[$field] . '</div>';
                        }

                        $parentPage = $data['campus'];
                        $parentContent = $container->get('node')->searchIdentifierByPath($parentPage . '/content');
                        $parentId = $parentContent[0]['identifier'];
                        $parentCopy = $container->get('node')->searchIdentifierByPath($parentPage . '/copy');
                        $parentCopiesId = $parentCopy[0]['identifier'];
                        $parent = $container->get('sulu.content.mapper')->load($parentId, 'example', 'en');
                        $url = $data['url'];
                        $newUrl = $parent->toArray()['url'] . '/' . trim(strtolower(preg_replace('/--+/', '-', preg_replace("/[^a-zA-Z0-9]/", "-", $data['title']))), '-');
                        $rec = new Request();
                        $rec->request->set('title', $data['title']);
                        $rec->request->set('h1_title', $data['title']);
                        $rec->request->set('headerText', $data['header_text']);
                        $rec->request->set('tagText', $data['tag']);
                        $rec->request->set('oldUrl', $url);
                        $rec->request->set('sourceOfPage', strtoupper($data['campus'].'-university'));
                        $rec->request->set('lastUpdate', date("Y-m-d"));
                        $rec->request->set('url', $newUrl);
                        $rec->request->set('sharedLink', $data['brochure_url']);
                        $rec->request->set('hideIn', false);
                        $rec->request->set('disableProgramNav', false);
                        $rec->request->set('template', "product-single");
                        $rec->request->set('date', date('Y-m-d'));
                        $rec->request->set('blocks', [
                            [
                                "type" => "text-block",
                                "containertype" => "default",
                                "sort" => 0,
                                "addToNav" => $data['maint_text'] != '' || $data['text_block'] != '' ? true : false,
                                "nameAnchor" => $data['maint_text'] != '' || $data['text_block'] != '' ? "About" : '',
                                "textBlockStyle" => "medium",
                                "content" => (str_replace(["'", "`", "‘", "’"], '\'', $data['maint_text'] . $data['text_block']))
                            ],
                            [
                                "type" => "accordion_accordion",
                                "accordionPosition" => "left",
                                "sort" => 4,
                                "containertype" => "default",
                                "title0" => $data['entry_requirements'] != '' ? "Entry Requirements" : '',
                                "content0" => $data['entry_requirements'],
                                "title1" => $data['syllabs'] != '' ? "Syllabus" : '',
                                "content1" => $data['syllabs'],
                                "title2" => $data['employability'] != '' ? "Employability" : '',
                                "content2" => $data['employability'],
                                "title3" => $data['fees_block'] != '' ? "Fees and funding" : '',
                                "content3" => $data['fees_block'],
                                "title4" => $data['fees_and_funding'] != '' ? "Fees and funding" : '',
                                "content4" => $data['fees_and_funding'],
                                "title5" => $data['careers'] != '' ? "Careers" : '',
                                "content5" => $data['careers'],
                            ]
                        ]);
                        $fees = $data['fees'] != '' ? $data['fees'] : ($data['uk_eu_fees'] != '' ? $data['uk_eu_fees'] : ($data['international_fees'] != '' ? $data['international_fees'] : ''));
                        if (strpos($fees, '£') == false && strpos($fees, ',') == false) {
                            $fees = '£' . number_format($fees, 0, '', ',');
                        }
                        $rec->request->set('info_modules', [
                            [
                                "type" => "programme-info-module",
                                "containertype" => "default",
                                "sort" => 0,
                                "startDates" => $data['start_dates'],
                                "duration" => $data['duration'],
                                "titleAward" => 'Format',
                                "award" => $data['award'],
                                "fees" => $fees,
                                "specialisations" => $data['specialisations'],
                            ]
                        ]);
                        $existenceNodeIds = $container->get('node')->searchIdentifierByOldUrl($url);
                        if (count($existenceNodeIds) > 0) {
                            foreach ($existenceNodeIds as $existenceNodeId) {
                                $existenceNodes = $container->get('sulu_document_manager.node_manager')->find($existenceNodeId['identifier']);
                                $parentDocumentId = $existenceNodes->getParent()->getIdentifier();
                                if ($container->get('node')->checkFullUpdating($existenceNodeId['identifier'])) {
                                    $this->updateDocument($rec, $existenceNodeId['identifier'], $language, $container, $parentDocumentId);
                                } else {
                                    $document = $container->get('sulu_document_manager.document_manager')->find(
                                        $existenceNodeId['identifier'],
                                        $language,
                                        [
                                            'load_ghost_content' => false,
                                            'load_shadow_content' => false,
                                        ]
                                    );
                                    $dataForUpdates = $document->getStructure()->toArray();
                                    $dataForUpdates['info_modules'][0]['fees'] = $fees;
                                    $dataForUpdates['info_modules'][0]['duration'] = $data['duration'];
                                    $dataForUpdates['last_update'] = date("Y-m-d");
                                    $dataForUpdates['h1_title'] = $data['title'];

                                    $rec2 = new Request();
                                    foreach ($dataForUpdates as $nameForUpdate => $dataForUpdate) {
                                        $rec2->request->set($nameForUpdate, $dataForUpdate);
                                    }
                                    $this->updateDocument($rec2, $existenceNodeId['identifier'], $language, $container, $parentDocumentId);
                                }
                            }
                        } else {
                            $document = $container->get('sulu_document_manager.document_manager')->create($type);
                            $formType = $container->get('sulu_document_manager.metadata_factory.base')->getMetadataForAlias($type)->getFormType();
                            $this->persistDocument($rec, $formType, $document, $language, $container, $parentId);
                            $container->get('sulu_document_manager.document_manager')->flush();
                            $nodeId = $container->get('node')->getPrimaryKey();
                            if (!empty($nodeId)) {
                                $container->get('node')->saveOldUrl($nodeId['identifier'], $url);
                            }
                            $copiedPath = $container->get('sulu_document_manager.document_manager')->copy(
                                $container->get('sulu_document_manager.document_manager')->find($nodeId['identifier'], $language), $parentCopiesId);
                            $container->get('sulu_document_manager.document_manager')->flush();
                        }
                    }
                }
            }
        }


        $this->getContainer()->get('sqs')
            ->deleteMessage($this->getQueueName(), $message['ReceiptHandle']);

    }

    public function persistDocument(Request $request, $formType, $document, $language, $container,$parentId)
    {
        $data = $request->request->all();

        $data['parent'] = $parentId;

        $form = $container->get('form.factory')->create(
            $formType,
            $document,
            [
                'csrf_protection' => false,
                'webspace_key' => 'example',
            ]
        );
        $form->submit($data, false);

        if (!$form->isValid()) {
            throw new InvalidFormException($form);
        }

        $container->get('sulu_document_manager.document_manager')->persist(
            $document,
            $language,
            [
                'user' => 28,
                'clear_missing_content' => false,
            ]
        );
    }

    public function updateDocument(Request $request, $uuid, $language, $container, $parentId)
    {
        $document = $container->get('sulu_document_manager.document_manager')->find(
            $uuid,
            $language,
            [
                'load_ghost_content' => false,
                'load_shadow_content' => false,
            ]
        );

        $formType = $container->get('sulu_document_manager.metadata_factory.base')->getMetadataForClass(get_class($document))->getFormType();

        $container->get('sulu_hash.request_hash_checker')->checkHash($request, $document, $document->getUuid());
        $this->persistDocument($request, $formType, $document, $language, $container, $parentId);

        $container->get('sulu_document_manager.document_manager')->flush();

    }

}
