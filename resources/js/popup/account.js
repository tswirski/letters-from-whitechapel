var popupAccount = (function () {
    var BOX = '.popup[data-popup="account"]';
    var SELECTOR_TEXT_INPUT = 'input[data-type="text"]';
    var SELECTOR_TEXT_INPUT_PLACEHOLDER = '.textInputPlaceholder';
    var SELECTOR_ERROR = 'label.error';
    var SELECTOR_AVATAR_FILE_INPUT = '.avatarBox input[type="file"]';
    var SELECTOR_AVATAR_REMOVE_CHECKBOX = '.avatarBox input[type="checkbox"]';
    var SELECTOR_AVATAR_REMOVE_CHECKBOX_LABEL = '.avatarBox label[for="checkbox-avatar-remove"]';
    var SELECTOR_AVATAR_IMAGE = '.avatarBox img';

    var CLASS_HIDDEN = 'hidden';
    var FILE_SIZE_LIMIT = 1024000;
    var MEGABYTE = 1024000; // 1MB

    /**
     * Returns jQuery object selected relatively to page selector.
     * @param {string} selector
     * @returns {*|jQuery}
     */
    var getBySelector = function(selector){
        return $(BOX).find(selector);
    };


    /**
     * Ukrycie komunikatu błędu dla podanego input'a
     * @param {string} inputName
     * @returns {undefined}
     */
    var hideErrorFor = function (inputName) {
        getBySelector('.error[for="input-' + inputName + '"]').hide({
            direction: "down",
            effect: "drop",
            duration: 150,
            complete: function () {
                $(this).text('');
            }
        });
    }

    /**
     * Pokazanie komunikatu o błędach dla podanych inputów.
     * @param {object} errors
     * @returns {undefined}
     */
    var showErrors = function (errors) {
        _.each(errors, function (value, key) {
            var $errorBox = getBySelector('.error[for="input-' + key + '"]');
            $errorBox.html(value);
            $errorBox.show({
                direction: "up",
                effect: "drop",
                duration: 150,
                complete: function () {
                }
            });
        });
    };

    /**
     * OBSŁUGA WYSYŁANIA DANYCH Z UWZGLĘDNIENIEM AVATARA
     */

    var getAccountData = function(){
        var data = {};

        if (!getBySelector('input#input-email').is(':disabled')) {
            data.email = getBySelector('input#input-email').val();
        }

        if (!getBySelector('input#input-password').is(':disabled')) {
            data.password = getBySelector('input#input-password').val();
        }

        return data;
    };

    /**
     * Gdy plik avatara jest obecny, a jego wielkośc odpowiednia lub gdy plik avatara nie jest obecny
     * następuje wysłanie danych na serwer. Jeśli plik avatara obecny ale nie spełnia kryteriów to
     * na serwer wysyłane jest zapytanie o walidację pól danych ale dane nie są uaktualniane.
     * Zwracany zawsze FALSE gdyż jest to Handler okienka dialogowego.
     * @returns {boolean} FALSE
     */
    var sendAccountData = function(){
        var fileData = getAvatarFileFromCache();
        if (fileData !== null && fileData.size > FILE_SIZE_LIMIT) {
            showSizeError();
            JsonRpc.notify('account.validateAccountData', {data: getAccountData()});
            return false;
        }
        JsonRpc.request('account.updateAccountData', {
            data: getAccountData()
        }, {
            onSuccess: function(result){
                if(result === true){
                    $.popupManager.hide();
                }
            }
        });
        return false;
    };



    /* @var {object | null} aktualnie wykonywany ajax */
    //var avatarUploadXhrOrNull = null;

    /**
     * OBSŁUGA WYSŁANIA PLIKU AVATARA
     */

    /**
     * Anuluje aktywny upload (jeśli istnieje).
     * Rozpoczyna nowy upload.
     * Rozpoczyna AJAX uploadujący obrazek avatara,
     * Pokazuje miniaturkę uploadowanego zdjęcia wpietą w ciało strony.
     * Miniaturka posiada dobindowany obserwer postępu (+ wskaźnik postępu)
     * oraz zostanie ukryta gdy postęp osiągnie 100%.
     */

    var sendAvatarDataAjax = function(userId, wsToken){
        //if (avatarUploadXhrOrNull !== null) {
        //    avatarUploadXhrOrNull.abort();
        //}

        var doDelete = getBySelector('#checkbox-avatar-remove').is(':checked');
        var requestJson = JsonRpc.getRequestJson('avatar.update', {
            wsToken: wsToken,
            userId: userId,
            delete: doDelete
        });

        /** Kasowanie pliku */
        if( doDelete === true){
            JsonHttpRpc.send(requestJson);
            if(isAvatarFileInputCached()){
                unsetAvatarFileInputCache();
            }
            return;
        }

        /** Brak wybranego obrazka */
        if( ! isAvatarFileInputCached()){
            return;
        }

        var avatarFile = getAvatarFileFromCache();
        unsetAvatarFileInputCache();

        var formData = new FormData();
        formData.append('json', requestJson);
        formData.append('0', avatarFile);
        JsonHttpRpc.send(formData)
    };

    /**
     * Ukryj komunikat błedu wielkości pliku avatara.
     */
    var hideSizeError = function(){
        hideErrorFor('avatar');
    };

    /**
     * Pokazuje komunikat błędu wielkości pliku avatara.
     * @param {int} sizeLimit
     * @returns {undefined}
     */
    var showSizeError = function () {
        var mb = FILE_SIZE_LIMIT / MEGABYTE;
        showErrors({
            avatar: "Max file size is: " + mb + " MB"
        });
    };

    /**
     * Ukrycie przycisku usuwania avatara oraz zaznaczenie checkboxu
     */
    var hideAvatarRemoveButtonAndSetRemoveCheckBox = function(){
        getBySelector(SELECTOR_AVATAR_REMOVE_CHECKBOX).prop('checked', true);
        getBySelector(SELECTOR_AVATAR_REMOVE_CHECKBOX_LABEL).hide();
    };

    /**
     * Pokazanie przycisku usuwania avatara oraz odznaczenie checkboxu
     */
    var showAvatarRemoveButtonAndUnsetRemoveCheckBox = function(){
        getBySelector(SELECTOR_AVATAR_REMOVE_CHECKBOX_LABEL).show();
        getBySelector(SELECTOR_AVATAR_REMOVE_CHECKBOX).prop('checked', false);
    };

    /**
     * -----------------
     *  OBSLUGA AVATARA
     * -----------------
     */

    /** @var {object | null} obiekt jQuery dla input[type="file"] */
    var $avatarFileInputCache  = null;

    /**
     * Przenosi podany obiekt input[type="file"] do cacheu. Podmienia orginalny obiekt
     * na jego kopię tak aby zachować funkcjonalność formularza.
     * @param $input
     */
    var setAvatarFileInputCache = function($input){
        $avatarFileInputCache = $input;
        var $clone = $avatarFileInputCache.clone();
        initFileInput($clone);
        $input.replaceWith($clone);
    }

    /**
     * Usuwa zawartość cacheu avatar'a
     */
    var unsetAvatarFileInputCache = function(){
        $avatarFileInputCache = null;
    };

    /**
     * Zwraca TRUE jeśli cache zawiera obiekt input[type="file"]
     * @returns {boolean}
     */
    var isAvatarFileInputCached = function(){
      return $avatarFileInputCache !== null;
    };

    /**
     * Zwraca zawartość cacheu avatar'a;
     * @return {object | null}
     */
    var getAvatarFileInputFromCache = function(){
        return $avatarFileInputCache;
    };

    /**
     * Zwraca obiekt pliku z obiektu jQuery odpowiadającemu input[type=file]
     * @param {object}
     * @returns {object}
     */
    var getFileObjectFromFileInput = function($inputFile){
        return $inputFile[0]['files'][0];
    };

    /**
     * Zwraca dane o pliku na podstawie scacheowanego input[type=file]
     * @returns {null | object}
     */
    var getAvatarFileFromCache = function(){
        if(!isAvatarFileInputCached()){
            return null
        }
        return getFileObjectFromFileInput(getAvatarFileInputFromCache());
    }

    /**
     * Tworzy pogląd avatara na podstawie cacheu obiektu input[type="file"];
     * Lub domyślnego avatara zachęty gdy nie wybrnao żadnego avatara.
     */
    var avatarUpdatePreview = function(){
        if(!isAvatarFileInputCached()){
            getBySelector(SELECTOR_AVATAR_IMAGE).attr('src', Avatar.getDefaultAvatarPath());
            return;
        }

        var fileData = getAvatarFileFromCache();
        var oFReader = new FileReader();
        oFReader.readAsDataURL(fileData);
        oFReader.onload = function (oFREvent) {
            getBySelector(SELECTOR_AVATAR_IMAGE).attr('src', oFREvent.target.result);
        };
    };


    /**
     * Inicjalizacja pola INPUT[file]
     */
    var initFileInput = function($fileInput){
        $fileInput.on('change', function () {
            if ( ! $(this).val()) {
                return false;
            }

            showAvatarRemoveButtonAndUnsetRemoveCheckBox();
            setAvatarFileInputCache($(this));
            avatarUpdatePreview();
        });
    };

    /**
     * INICJALIZACJA TEGO BAJZLU
     */
    var initContent = function () {

        /** KASOWANIE BŁĘDÓW INPUTÓW */
        getBySelector(SELECTOR_TEXT_INPUT).on('focus keydown click', function (event) {
            $(this).siblings(SELECTOR_ERROR).hide({
                direction: "down",
                effect: "drop",
                duration: 150,
                complete: function () {
                }
            });
        });

        /**
         * Wciśnięcie klawisza ENTER = SUBMIT
         */
        getBySelector(SELECTOR_TEXT_INPUT).on('keydown', function(event){
           if(event.which === 13){
               sendAccountData();
           }
        });

        /** KLIKNIĘCIE W ZAJAWKĘ POKAZUJE INPUT */
        getBySelector(SELECTOR_TEXT_INPUT_PLACEHOLDER).on('click', function () {
            $(this)
                .addClass(CLASS_HIDDEN);
            $(this)
                .next(SELECTOR_TEXT_INPUT)
                .attr('disabled', null)
                .removeClass(CLASS_HIDDEN)
                .trigger('focus');
        });

        /** :BLUR INPUTU NIE POSIADAJĄCEGO ZAWARTOŚCI */
        getBySelector(SELECTOR_TEXT_INPUT).on('blur', function () {
            if ($(this).val()) {
                return;
            }

            $(this).siblings(SELECTOR_ERROR).hide({
                direction: "down",
                effect: "drop",
                duration: 150,
                complete: function () {
                }
            });

            $(this)
                .attr('disabled', true)
                .addClass(CLASS_HIDDEN);
            $(this)
                .prev(SELECTOR_TEXT_INPUT_PLACEHOLDER)
                .removeClass(CLASS_HIDDEN);
        });

        /** USUNIĘCIE BŁĘDU ROZMIARU PLIKU Z OBRAZKA */
        getBySelector(SELECTOR_AVATAR_IMAGE).on('click', function () {
           hideSizeError();
        });

        /** OBSŁUGA POKAZANIA PRZYCISKU KASOWANIA AVATARA */
        var $image = getBySelector(SELECTOR_AVATAR_IMAGE);
        //var $avatarRemoveButton = $(SELECTOR_AVATAR_REMOVE_CHECKBOX_LABEL);

        if ($image.attr('src') !== Avatar.getDefaultAvatarPath()) {
            showAvatarRemoveButtonAndUnsetRemoveCheckBox();
        }

        /** OBSŁUGA PRZYCISKU USUWAJĄCEGO AVATAR */
        getBySelector(SELECTOR_AVATAR_REMOVE_CHECKBOX_LABEL).on('click', function (e) {
            e.preventDefault();
            hideAvatarRemoveButtonAndSetRemoveCheckBox();
            unsetAvatarFileInputCache();
            avatarUpdatePreview();
        });


        /** OBSŁUGA PODGLĄDU WYBRANEGO AVATARA */
        initFileInput(getBySelector(SELECTOR_AVATAR_FILE_INPUT));
    };

    /**
     * Render page
     */
    var init = function(userAvatarImg){
        Template.render('popup-account', {userAvatarImg: userAvatarImg}, function($pageContent){
            $.popupManager.dialog($pageContent, {
                onShow: initContent,
                okHandler: sendAccountData
            });
        });
    };

    return {
        init: init,
        showErrors: showErrors,
        sendAccountData : sendAccountData,
        sendAvatarDataAjax : sendAvatarDataAjax,
        getAvatarFileFromCache:getAvatarFileFromCache,
        getAvatarFileInputFromCache:getAvatarFileInputFromCache
    };
})();
