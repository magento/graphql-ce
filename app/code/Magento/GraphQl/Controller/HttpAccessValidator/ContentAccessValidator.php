<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\Controller\HttpAccessValidator;

use Magento\Framework\App\HttpRequestInterface;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\GraphQl\Controller\HttpRequestValidatorInterface;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Integration\Model\Oauth\Token;
use Magento\Integration\Model\Oauth\TokenFactory;
use Magento\Framework\Stdlib\DateTime\DateTime as Date;
use Magento\Framework\Stdlib\DateTime;
use Magento\Integration\Helper\Oauth\Data as OauthHelper;
/**
 * Class ContentAccessValidator
 *
 * @package Magento\GraphQl\Controller\HttpAccessValidator
 */
class ContentAccessValidator implements HttpRequestValidatorInterface
{
    /**
     * @var Token
     */
    protected $tokenFactory;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var Date
     */
    private $date;

    /**
     * @var OauthHelper
     */
    private $oauthHelper;

    /**
     * Initialize dependencies.
     *
     * @param TokenFactory $tokenFactory
     * @param DateTime|null $dateTime
     * @param Date|null $date
     * @param OauthHelper|null $oauthHelper
     */
    public function __construct(
        TokenFactory $tokenFactory,
        DateTime $dateTime = null,
        Date $date = null,
        OauthHelper $oauthHelper = null
    ) {
        $this->tokenFactory = $tokenFactory;
        $this->dateTime = $dateTime ?: ObjectManager::getInstance()->get(
            DateTime::class
        );
        $this->date = $date ?: ObjectManager::getInstance()->get(
            Date::class
        );
        $this->oauthHelper = $oauthHelper ?: ObjectManager::getInstance()->get(
            OauthHelper::class
        );
    }


    /**
     * @inheritdoc
     */
    public function validate(HttpRequestInterface $request) : void
    {
        $this->processRequest($request);
    }

    /**
     * Check if token is expired.
     *
     * @param Token $token
     * @return bool
     */
    private function isTokenExpired(Token $token): bool
    {
        if ($token->getUserType() == UserContextInterface::USER_TYPE_CUSTOMER) {
            $tokenTtl = $this->oauthHelper->getCustomerTokenLifetime();
        } else {
            // other user-type tokens are considered always valid
            return false;
        }

        if (empty($tokenTtl)) {
            return false;
        }

        if ($this->dateTime->strToTime($token->getCreatedAt()) < ($this->date->gmtTimestamp() - $tokenTtl * 3600)) {
            return true;
        }

        return false;
    }

    /**
     * Checked the bearer token
     *
     * @param HttpRequestInterface $request
     * @return void
     *
     * @throws GraphQlInputException
     */
    protected function processRequest(HttpRequestInterface $request) : void
    {
        $authorizationHeaderValue = $request->getHeader('Authorization');
        if ($authorizationHeaderValue) {
            $headerPieces = explode(" ", $authorizationHeaderValue);
            if (count($headerPieces) !== 2) {
                throw new GraphQlInputException(__("Athorization headers parts limitation is wrong"));
            }

            $tokenType = strtolower($headerPieces[0]);
            if ($tokenType !== 'bearer') {
                throw new GraphQlInputException(__("Athorization token is wrong"));
            }

            $bearerToken = $headerPieces[1];
            $token = $this->tokenFactory->create()->loadByToken($bearerToken);

            if (!$token->getId() || $token->getRevoked() || $this->isTokenExpired($token)) {
                throw new GraphQlInputException(__("The current customer isn't authorized."));
            }
        }
    }
}
