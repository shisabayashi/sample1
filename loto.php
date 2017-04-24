<?php
use Lib\Util\CsvFile;
use Phalcon\Mvc\Model;

class Sample extends Model
{
    private $logger;

    /**
     * 初期化
     */
    public function initialize()
    {
        $this->logger = $this->getDI()->get('logger');
    }

    /**
     * CSVファイルを読み込み
     *
     * @param string $csvFilePath
     * @return array|string $csvRecords
     */
    public function getMemberCdFromCsv($csvFilePath)
    {
        $csvRecords = null;
        if (!file_exists($csvFilePath)) {
            $this->logger->notice('csv [file not found :file_path= ' .$csvFilePath .']');
            return null;
        }
        try {
            $this->logger->debug('csv [file read :file_path=' .$csvFilePath .']');
            $csvRecords = CsvFile::readCsvFile($csvFilePath);
        } catch (\Exception $e) {
            $this->logger->error('csv [' .$e->getMessage() .']');
        }
        return $csvRecords;
    }

    /**
     * 会社情報登録
     * companyテーブルに未登録の場合はInsert
     * 登録済の場合はコメントをupdate
     *
     * @param string $companyName
     * @param string $memberName
     * @param string $comments
     * @return bool $errStatus
     */
    public function CompanyInfoUpdate($companyName, $memberName, $comments)
    {
        $findCompany = $this->findCompany($companyName, $memberName);
        $findCompanyArray = $findCompany->toArray();
        $errStatus = false;
        if (empty($findCompanyArray)) {
            // insert
            $this->logger->info('_company [insert execute :_id=' .$companyName .']');
            $insertResults = $this->insertCompany($companyName, $memberName, $comments);
            if (!$insertResults) {
                $this->logger->error('_company [insert failed :_id=' .$companyName .']');
                $errStatus = true;
            }
            return $errStatus;
        }
        if (count($findCompanyArray) > 1 ||
            ($findCompanyArray[0]['_id'] !== $companyName)) {
            $this->logger->error('_company [duplicate registration :_id=' .$companyName .'|member_name=' .$memberName .']');
            $errStatus = true;
            return $errStatus;
        }
        // update
        $this->logger->info('_company [update execute :_id=' .$companyName .']');
        $updateResults= $this->updateCompany($findCompany, $comments);
        if (!$updateResults) {
            $this->logger->error('_company [update failed :_id=' .$companyName .']');
            $errStatus = true;
        }
        return $errStatus;
    }
    /**
     * ユーザ情報登録
     * userテーブルに未登録の場合はInsert
     * updateは無し
     *
     * @param string $companyName
     * @param MemberInfo $memberInfoUsers
     * @return array $memberCdArray, $errFlg
     */
    public function UserInfoUpdate($companyName, $memberInfoUsers)
    {
        $memberCdArray = array();
        $errFlg = false;
        $db = $this->getDI()->get('db');
        $db->begin();
        foreach ($memberInfoUsers as $user) {
            $memberCd = $user->getMemberCd();
            $email = $user->getEmail();
            if ($user->isEmptyItem()) {
                $this->logger->error('_user [empty data :member_cd=' .$memberCd .'|last_name=' .$user->getLastName() .'|first_name=' .$user->getFirstName() .'|email=' .$email .']');
                $errFlg = true;
                continue;
            }
            // emailはuniqueなので1件しかとれない
            $findUser = $this->findUser($email);
            if (!$findUser) {
                // insert
                $this->logger->info('_user [insert execute :member_cd=' .$memberCd .']');
                $insertResults = $this->insertUser($companyName, $user);
                if (!$insertResults) {
                    $this->logger->error('_user [insert failed :_id=' .$companyName .'|email=' .$email .']');
                    $errFlg = true;
                    continue;
                }
            } else {
                $Id = $findUser->_id;
                if ($companyName !== $Id) {
                    $this->logger->error('_user [registered email address :_id=' .$companyName .'|member_cd=' .$memberCd .'|email=' .$email .']');
                    $errFlg = true;
                    continue;
                }
                $this->logger->info('_user [registered email address. same company :_id=' .$companyName .'|member_cd=' .$memberCd .'|email=' .$email .']');
            }
            $memberCdArray[] = $user->getMemberCd();
        }// end foreach
        $db->commit();
        return array($memberCdArray, $errFlg);
    }
    /**
     * companyテーブルから検索結果取得
     *
     * @param string $Id
     * @param string $memberName
     * @return Phalcon\Mvc\Model\ResultsetInterface  $findResults
     */
    protected function findCompany($Id, $memberName)
    {
        // find
        return Company::find(
            [
                "_id = :_id: OR name = :name:",
                "bind" => [
                    '_id'  => $Id,
                    'name' => $memberName
                ],
            ]
        );
    }
    /**
     * userテーブルから検索結果取得
     *
     * @param string $email
     * @return Phalcon\Mvc\Model $findResults
     */
    protected function findUser($email)
    {
        // find
        return User::findFirst(
            [
                "email = ?1",
                "bind" => [
                    1 => $email,
                ],
            ]
        );
    }
    /**
     * companyテーブルへ登録
     *
     * @param string $Id
     * @param string $memberName
     * @param string $comments
     * @return bool $insertResults
     */
    protected function insertCompany($Id, $memberName, $comments)
    {
        // insert
        $Company = new Company();
        return $Company->create(
            [
                "_id" => $Id,
                "name"        => $memberName,
                "comments"    => $comments,
            ]
        );
    }
    /**
     * _userテーブルへ登録
     * first_nameにgetLastName()
     * last_nameにgetFirstName()をセットしているのはotrsの表示上の問題のため
     *
     * @param string $Id
     * @param MemberInfo $user
     * @return bool $insertResults
     */
    protected function insertUser($Id, $user)
    {
        // insert
        $User = new User();
        return $User->save(
            [
                "user"  => $user->getUser(),
                "email" => $user->getEmail(),
                "_id"   => $Id,
            ]
        );
    }
    /**
     * _companyテーブルの更新
     *
     * @param Phalcon\Mvc\Model\ResultsetInterface $findCompany
     * @param string $comments
     * @return bool $updateResults
     */
    protected function updateCompany($findCompany, $comments)
    {
        // update
        $findCompany->rewind();
        $Company = $findCompany->current();
        $Company->comments = $comments;
        return $Company->update();
    }

    /**
     * 会員情報のマッピング
     * APIのレスポンスをjsonに変換しMemberInfo Classへマッピング
     *
     * @param string $apiResponse
     * @return MemberInfo $memberInfo
     */
    protected function memberInfoJsonDataMapping($apiResponse)
    {
        $jsonMemberData = json_decode($apiResponse, true);
        $memberInfo = new MemberInfo();
        $memberInfo->setMemberCd($jsonMemberData['members']['membercd'] ?? '');
        $memberInfo->setMemberName($jsonMemberData['members']['membernm'] ?? '');
        $memberInfo->setLastName($jsonMemberData['members']['lastname'] ?? '');
        $memberInfo->setFirstName($jsonMemberData['members']['firstname'] ?? '');
        $memberInfo->setTel($jsonMemberData['members']['tel'] ?? '');
        $memberInfo->setEmail($jsonMemberData['members']['email'] ?? '');
        return $memberInfo;
    }
}
