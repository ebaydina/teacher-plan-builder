function rand(min, max) {
    if (max) {
        return Math.floor(Math.random() * (max - min + 1)) + min;
    } else {
        return Math.floor(Math.random() * (min + 1));
    }
}

function upload(success, error, init) {
    $("#image-uploader").off('change');
    $("#image-uploader").on('change', function () {
        const formData = new FormData();
        formData.append('token', userToken);
        formData.append('photo', this.files[0]);
        if (typeof (init) == "function") {
            init.call(null);
        }
        $.ajax({
            url: 'upload.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (res) {
                if (res.success !== undefined) {
                    if (typeof (success) == "function") {
                        success.call(null, res.success);
                    }
                } else {
                    if (typeof (res) !== "object") {
                        err('Incorrect data');
                        res = {error: ""};
                    }
                    if (typeof (error) == "function") {
                        error.call(null, res.error);
                    }
                    if (['Session not found', 'Session expired'].indexOf(res.error) !== -1) {
                        $('section').addClass('d-none');
                        $("#signin").removeClass('d-none');
                    }
                }
            },
            error: function (a, b, c) {
                if (c == '') {
                    c = 'Unknown error';
                }
                if (typeof (error) == "function") {
                    error.call(null, '');
                }
                err(c);
            }
        });
    });
    $("#image-uploader").val("").click();
}

function showSignInForm() {
    $("#panel, #signup").addClass('d-none');
    $("#signin").removeClass('d-none');
    title('Sign up');
}

function getMonth(num) {
    const months = [
        "January",
        "February",
        "March",
        "April",
        "May",
        "June",
        "July",
        "August",
        "September",
        "October",
        "November",
        "December"
    ];
    return months[num - 1] !== undefined ? months[num - 1] : '';
}

function renderCalendarConstructorImage(item) {
    return `
        <div class="item">
            <div class="item-image">
                <img src="` + item.photo + '?v=' + Version + `"/>
            </div>
            <div class="item-name">` + item.name + `</div>
        </div>
    `;
}

function eventsCalendarConstructorImages(id) {
    const items = $("#month-images .item, #letter-images .item").not('.init-events');
    items.addClass('init-events');
    items.disableSelection();
    items.click(function (e) {
        const self = $(this);
        const image = self.find('img').attr('src');
        self.addClass('selected');
        if (id !== undefined && id >= 0) {
            editImageElement(id, image);
            $("#calendar-images-window").modal('hide');
        } else {
            const cell = randCell();
            addImageElement(image, cell.x, cell.y);
        }
    });
}

function renderCalendarConstructorSheet(item) {
    return `
        <tr item-id="` + item.id + `" class="item">
            <td class="align-middle">` + item.id + `</td>
            <td class="align-middle">` + item.name + `</td>
            <td class="align-middle">` + getMonth(item.data.month) + ((new Date).getFullYear() !== item.data.year ? ' ' + item.data.year : '') + `</td>
            <td class="align-middle">
                <div class="item-control">
                    <div class="item-print btn btn-primary">
                        <span class="spinner-border spinner-border-sm d-none"></span>
                        <span>Print</span>
                    </div>
                    <div class="item-edit btn btn-warning">
                        <span class="spinner-border spinner-border-sm d-none"></span>
                        <span>Edit</span>
                    </div>
                    <div class="item-remove btn btn-danger">
                        <span class="spinner-border spinner-border-sm d-none"></span>
                        <span>Remove</span>
                    </div>
                </div>
            </td>
        </tr>
    `;
}

function eventsCalendarConstructorSheets() {
    $("#draft-list .item-print").click(function () {
        const self = $(this);
        spinner(self, true);
        const id = parseInt(self.parent().parent().parent().attr('item-id')) || 0;
        showCalendarConstructor(false, id, function (res) {
            if (res) {
                calendarPrint(id, '#calendar-constructor-tmp .a4', function (res) {
                    spinner(self, false);
                });
            } else {
                spinner(self, false);
            }
        });
    });
    $("#draft-list .item-edit").click(function () {
        const self = $(this);
        autoSaveData = localStorage.getItem('calendar-autosave');
        if (autoSaveData !== null) {
            try {
                autoSaveData = JSON.parse(autoSaveData);
                if (typeof (autoSaveData) == "object") {
                    $($("#calendar-autosaved-new span")[1]).text('Edit selected calendar');
                    $("#calendar-autosaved-new").attr('item-id', self.parent().parent().parent().attr('item-id'));
                    $("#calendar-load-autosave").modal('show');
                    return false;
                }
            } catch (e) {
            }
        }
        showCalendarConstructor(false, parseInt(self.parent().parent().parent().attr('item-id')) || 0);
    });
    $("#draft-list .item-remove").click(function () {
        const self = $(this);
        spinner(self, true);
        api('calendarConstructorSheetRemove', {
            id: self.parent().parent().parent().attr('item-id'),
        }, function (res) {
            spinner(self, false);
            self.parent().parent().parent().remove();
            if ($("#draft-list .item").length == '') {
                $("#draft-list").html(`
                    <tr class="text-center">
                        <td colspan="4">No calendar was ever created</td>
                    </tr>
                `);
            }
            suc(res);
        }, function (res) {
            spinner(self, false);
            err(res);
        });
    });
}

function aesEncrypt(text) {
    const iv = CryptoJS.lib.WordArray.random(16);
    const salt = CryptoJS.lib.WordArray.random(256);
    const iterations = 999;
    const hashKey = CryptoJS.PBKDF2(clientToken, salt, {
        'hasher': CryptoJS.algo.SHA512,
        'keySize': (64 / 8),
        'iterations': iterations
    });
    const encrypted = CryptoJS.AES.encrypt(text, hashKey, {
        'mode': CryptoJS.mode.CBC,
        'iv': iv
    });
    const encryptedString = CryptoJS.enc.Base64.stringify(encrypted.ciphertext);
    const output = {
        'text': encryptedString,
        'iv': CryptoJS.enc.Hex.stringify(iv),
        'salt': CryptoJS.enc.Hex.stringify(salt),
        'iterations': iterations
    };
    return CryptoJS.enc.Base64.stringify(CryptoJS.enc.Utf8.parse(JSON.stringify(output)));
}

function api(method, params, success, error) {
    if (typeof (params) == "function") {
        error = success;
        success = params;
    }
    if (typeof (params) !== "object") {
        params = {};
    }
    params.method = method;
    if (userToken !== undefined) {
        params.token = userToken;
    }
    $.post('/api.php', params, function (res) {
        if (res.success !== undefined) {
            if (typeof (success) == "function") {
                success.call(null, res.success);
            }
        } else {
            if (typeof (res) !== "object") {
                err('Incrorrect data');
                res = {error: ""};
            }
            if (typeof (error) == "function") {
                error.call(null, res.error);
            }
            if (['Session not found', 'Session expired'].indexOf(res.error) !== -1) {
                $('section').addClass('d-none');
                $("#signin").removeClass('d-none');
            }
        }
    }).fail(function (a, b, c) {
        if (c == '') {
            c = 'Unknown error';
        }
        if (typeof (error) == "function") {
            error.call(null, '');
        }
        err(c);
    });
}

function loader(status, el, text) {
    if (typeof (text) != "string") {
        text = "";
    }
    if (el === undefined) {
        el = $("#loader");
    } else if (typeof (el) == "string") {
        text = el;
        el = $("#loader");
    }
    const elText = el.find('.loader-text');
    if (elText.length) {
        if (text === "") {
            elText.addClass('d-none');
        } else {
            elText.html(text);
            elText.removeClass('d-none');
        }
    }
    if (status === true) {
        el.removeClass('d-none');
    } else {
        el.addClass('d-none');
    }
}

function msg(text, type, el) {
    if (typeof (text) !== "string") {
        return true;
    }
    let isGlobalResult = false;
    if (el === undefined) {
        el = '#global-result';
        isGlobalResult = true;
    }
    const obj = $(el);
    obj.addClass('d-none');
    let alertObj = obj;
    if (isGlobalResult) {
        alertObj = obj.find('.alert');
    }
    alertObj.html(text).removeClass(type ? 'alert-danger' : 'alert-success').addClass(type ? 'alert-success' : 'alert-danger');
    obj.removeClass('d-none');
    if (window.msg_timer === undefined) {
        window.msg_timer = {};
    }
    if (window.msg_timer[el] !== undefined) {
        clearTimeout(window.msg_timer[el]);
    }
    window.msg_timer[el] = setTimeout(function () {
        obj.addClass('d-none');
    }, 30000);

}

function suc(text, el) {
    if (text.length) {
        msg(text, true, el);
    }
}

function err(text, el) {
    if (text.length) {
        msg(text, false, el);
    }
}

function getUserProfile() {
    title('Control panel');
    loader(true);
    $("#signin, #signup, #verify, #settings, #subscription").addClass('d-none');
    api('getProfile', function (res) {
        user = res;

        api('CalendarConstructorImages', function (res) {
            calendarImages = res;
            const filesOfAllCategories = getFilesOfAllCategories(
                calendarImages
            );

            function extractCategoriesImages(obj) {
                const myNodes = [];
                for (let key in obj) {
                    let nodes = [];
                    if (typeof (obj[key]) === "object") {
                        const child = obj[key];

                        nodes = extractCategoriesImages(child);
                    }

                    let node = [];
                    if (nodes.length === 0) {
                        node = {"text": key};
                    }
                    if (nodes.length !== 0) {
                        node = {"text": key, "nodes": nodes};
                    }

                    myNodes.push(node);
                }
                return myNodes;
            }
            const imagesList = extractCategoriesImages(
                filesOfAllCategories
            );

            $("#calendar-constructor-edit-image-search").treeview({
                levels: 1,
                data: imagesList,
                expandIcon: 'bi bi-chevron-down',
                collapseIcon: 'bi bi-chevron-right',
                emptyIcon: 'no-icon',
                nodeIcon: '',
                selectedIcon: '',
                checkedIcon: '',
                uncheckedIcon: '',
                highlightSelected: true,
                selectedBackColor: '#0d6efd',
                selectedColor: 'white',
                borderColor: false,
                backColor: false,
                color: false,
                onNodeSelected: function (a, b, c) {
                    selectedConcept = b;
                },
                onNodeUnselected: function () {
                    selectedConcept = false;
                }
            });

        }, function (error) {
            err(error)
        });

        api('GetConcepts', function (res) {
            conceptsList = res;

            $("#calendar-constructor-edit-text-search").treeview({
                levels: 1,
                data: conceptsList,
                expandIcon: 'bi bi-chevron-down',
                collapseIcon: 'bi bi-chevron-right',
                emptyIcon: 'no-icon',
                nodeIcon: '',
                selectedIcon: '',
                checkedIcon: '',
                uncheckedIcon: '',
                highlightSelected: true,
                selectedBackColor: '#0d6efd',
                selectedColor: 'white',
                borderColor: false,
                backColor: false,
                color: false,
                onNodeSelected: function (a, b, c) {
                    selectedConcept = b;
                },
                onNodeUnselected: function () {
                    selectedConcept = false;
                }
            });

        }, function (error) {
            err(error)
        });

        if (user['allow'] === true && user.admin === 0) {
            $("#book-full-price").addClass('d-none');
            $("#book-with-discount").removeClass('d-none');
        }
        if (user['allow'] === false && user.admin === 0) {
            $("#book-full-price").removeClass('d-none');
            $("#book-with-discount").addClass('d-none');
        }

        if (user.admin === 1) {
            $("#menu-item-filemanager-btn").removeClass('d-none');
            $("#filemanager-btn").click(function () {
                if (user.admin === 1) {
                    window.open('/filemanager/', '_blank');
                }
            });
        }
        if (user.admin !== 1) {
            $("#menu-item-filemanager-btn").addClass('d-none');
        }
        if (user['allow'] !== true) {
            $("#menu-item-draft-btn").addClass('d-none');
            $("#menu-item-name-constructor-btn").addClass('d-none');
        }
        if (user['allow'] === true) {
            $("#menu-item-draft-btn").removeClass('d-none');
            $("#menu-item-name-constructor-btn").removeClass('d-none');

            $("td form").each(function (key, value) {
                if (this.action.indexOf('&token=') === -1) {
                    this.action = `${this.action}\&token=${userToken}`;
                }
            });

            const emptySubscriptionList = `
<table
        id='subscription-list'
        class="table table-hover table-bordered border-primary">
    <caption>
        List of my actual subscriptions
    </caption>
    <thead>
    <tr>
        <th scope="col">Title</th>
        <th scope="col">Type</th>
        <th scope="col">Starts</th>
        <th scope="col">Ends</th>
    </tr>
    </thead>
    <tbody>
    </tbody>
</table>
`;

            document
                .getElementById("subscription-list")
                .outerHTML =
                res["subscription-list"]
                ?? emptySubscriptionList;
            delete res["subscription-list"];

            $("#panel").removeClass('d-none');
            $("#settings-name").val(user.name);
            $("#settings-surname").val(user.surname);
            $("#settings-interests").val(user.interests);
            $("#settings-about").val(user.about);
            $("#settings-email").val(user.email);
            $("#settings-gender").val(user.gender.toString());
            $("#select-week-type").val(parseInt(user.week_type) || 0);
            if (user.photo.length) {
                spinner($("#user-avatar").parent(), true);
                spinner($("#settings-user-avatar").parent(), true);
                $("#user-avatar, #settings-user-avatar").each(function (i, e) {
                    e.src = user.photo;
                });
                $("#settings-avatar-remove-btn").removeClass('d-none');
            } else {
                $("#settings-avatar-remove-btn").addClass('d-none');
            }

            if (user.admin) {
                $('body').removeClass('is-user').addClass('is-admin');
                $("#filemanager-btn").removeClass('d-none');
            } else {
                $('body').removeClass('is-admin').addClass('is-user');
                $("#filemanager-btn").addClass('d-none');
            }
            if (user.admin) {
                $("#name-constructor-photos").sortable({
                    stop: function () {
                        const sortList = [];
                        $("#name-constructor-photos .item").each(function (i, e) {
                            const id = parseInt($(e).attr('item-id')) || 0;
                            if (id > 0) {
                                sortList.push(id);
                            }
                        });
                        api('nameConstructorSortPhotos', {ids: sortList.join(",")});
                    }
                });
            }

            if (
                constructorWasInitialized !== undefined
                && constructorWasInitialized === true
            ) {

                if (user['allow'] === true) {
                    $("#draft-btn").click();
                }
                if (user['allow'] === false) {
                    $("#subscription-btn").click();
                }

                loader(false);

                return;
            }
            constructorWasInitialized = true;

            $("#name-constructor-btn").click(function () {
                showNameConstructor();
            });
            $("#name-constructor-generate").click(function () {
                const self = $(this);
                spinner(self, true);
                const name = $("#name-constructor-name").val().replace(/[^A-z]/, '').split('');

                function generateRow(name, isLines) {
                    let html = '<div class="item-row">';
                    for (let i = 0; i < name.length; i++) {
                        const letter = name[i];
                        html += `
                    <div class="item` + (isLines === true ? ' line' : '') + `">
                        <img src="img/alphabet/` + letter.toLowerCase() + (letter === letter.toUpperCase() ? '_' : '') + `.png?v=` + Version + `">
                        <div></div>
                    </div>
                `;
                    }
                    html += '</div>';
                    return html;
                }

                let html = '';
                if (name.length) {
                    html += generateRow(name);
                    html += generateRow(name);
                    html += generateRow(name, true);
                    html += generateRow(name);
                    html += generateRow(name);
                    html += generateRow(name, true);
                }
                html += `
            <div class="copyright">
                <img src="img/copyright.png?v=` + Version + `">
            </div>`;
                $("#name-constructor-list-content .a4").html(html);
                $("#name-constructor-print").removeAttr('disabled');
                self.attr('disabled', '');
                spinner(self, false);
            });
            $("#name-constructor-print").click(function () {
                const self = $(this);
                spinner(self, true);
                html2canvas(document.querySelector("#name-constructor-list-content .a4"), {
                    allowTaint: true,
                    useCORS: true,
                    dpi: 300,
                    scale: 5
                }).then(canvas => {
                    const doc = new jspdf.jsPDF({
                        orientation: 'p',
                        unit: 'mm',
                        format: 'a4'
                    });
                    canvas.webkitImageSmoothingEnabled = false;
                    canvas.mozImageSmoothingEnabled = false;
                    canvas.imageSmoothingEnabled = false;
                    doc.addImage(canvas.toDataURL('image/png'), 'PNG', 0, 0, doc.internal.pageSize.width, doc.internal.pageSize.height);
                    doc.autoPrint();
                    window.open(doc.output('bloburl'), '_blank');
                    spinner(self, false);
                });
            });
            $("#calendar-constructor-add-sheet").click(function () {
                showCalendarConstructor(true);
            });
            $("#calendar-constructor-save").click(function () {
                if (sheet && sheet.id !== undefined) {
                    $("#calendar-save-sheet-name").val(sheet.name);
                }
                $("#calendar-constructor-save-window").modal('show');
            });
            $("#calendar-constructor-saved").click(function () {
                const self = $(this);
                const name = $("#calendar-save-sheet-name").val();
                if (!name.length) {
                    return err("Enter calendar name", $("#calendar-constructor-saved-result"));
                }
                spinner(self, true);
                let data = getCalendarData();
                data = JSON.stringify(data);
                const params = {
                    name: name,
                    data: data
                };
                if (sheet.id !== undefined) {
                    params.id = sheet.id;
                    api('calendarConstructorSheetEdit', params, function (res) {
                        suc('Calendar saved', "#calendar-constructor-saved-result");
                        spinner(self, false);
                        localStorage.removeItem('calendar-autosave');
                    }, function (res) {
                        err(res, "#calendar-constructor-saved-result");
                        spinner(self, false);
                    });
                } else {
                    api('calendarConstructorSheetAdd', params, function (res) {
                        sheet = res;
                        suc('Calendar saved', "#calendar-constructor-saved-result");
                        spinner(self, false);
                        localStorage.removeItem('calendar-autosave');
                    }, function (res) {
                        err(res, "#calendar-constructor-saved-result");
                        spinner(self, false);
                    });
                }
            })
            $("#draft-btn").click(function () {
                $('.page').addClass('d-none');
                loader(true, $("#page-loader"));
                api('getCalendarConstructorSheets', function (res) {
                    const list = $("#draft-list");
                    sheets = {};
                    if (!res.length) {
                        list.html(`
                    <tr class="text-center">
                        <td colspan="4">No calendar was ever created</td>
                    </tr>
                `);
                    } else {
                        const html = [];
                        for (let i = 0; i < res.length; i++) {
                            const item = res[i];
                            html.push(renderCalendarConstructorSheet(item));
                            sheets[item.id] = item;
                        }
                        list.html(html.join());
                        eventsCalendarConstructorSheets();
                    }
                    showDrafts();
                }, function (res) {
                    loader(false, $("#page-loader"));
                    err(res);
                });
            });
            $("#draft-add").click(function () {
                autoSaveData = localStorage.getItem('calendar-autosave');
                if (autoSaveData !== null) {
                    try {
                        autoSaveData = JSON.parse(autoSaveData);
                        if (typeof (autoSaveData) == "object") {
                            $("#calendar-autosaved-new").attr('item-id', 0);
                            $($("#calendar-autosaved-new span")[1]).text('Add New Draft');
                            $("#calendar-load-autosave").modal('show');
                            return false;
                        }
                    } catch (e) {
                    }
                }
                showCalendarMonthSelector();
            });
            $("#calendar-autosaved-load").click(function () {
                showCalendarConstructor(autoSaveData);
            });
            $("#calendar-autosaved-new").click(function () {
                const self = $(this);
                localStorage.removeItem('calendar-autosave');
                const itemId = parseInt(self.attr('item-id')) || 0;
                if (itemId > 0) {
                    showCalendarConstructor(false, itemId);
                    return;
                }
                $("#draft-add").click();
            })
            $("#calendar-to-edit-text").click(function () {
                showAddText();
            });
            $("#calendar-text-editor-add").click(function () {
                if (!selectedConcept) {
                    err("Please select concept", "#calendar-text-editor-result");
                    return false;
                }
                let count = 1;

                function getCountChilds(arr) {
                    let countElements = arr.length;
                    for (let i = 0; i < arr.length; i++) {
                        if (arr[i].nodes) {
                            countElements += getCountChilds(arr[i].nodes);
                        }
                    }
                    return countElements;
                }

                if (selectedConcept.nodes) {
                    count += getCountChilds(selectedConcept.nodes);
                }
                if (count > 1) {
                    $("#dialog-confirm-add-text-selected").text(count);
                    $("#calendar-text-editor").modal('hide');
                    $("#dialog-confirm-add-text").modal('show');
                } else {
                    addTextElement(selectedConcept.text, $("#calendar-constructor-edit-size").val(), $("#calendar-constructor-edit-color").val());
                    $("#calendar-text-editor").modal('hide');
                }
            });
            $("#calendar-image-editor-add").click(function () {
                if (!selectedConcept) {
                    err("Please select image group", "#calendar-image-editor-result");
                    return false;
                }

                addCategoryImages(selectedConcept.text);
                $("#calendar-text-editor").modal('hide');
            });
            $("#calendar-day-color-save").click(function () {
                if (selectDay) {
                    const color = $("#calendar-day-color").val();
                    selectDay.css({backgroundColor: color}).attr('color', color);
                }
            });
            $("#calendar-day-holiday-color").click(function () {
                if (selectDay) {
                    const color = '#ea868f';
                    selectDay.css({backgroundColor: color}).attr('color', color);
                }
            });
            $("#calendar-day-clear-color").click(function () {
                if (selectDay) {
                    const color = '#ffffff';
                    selectDay.css({backgroundColor: color}).attr('color', color);
                }
            });
            $("#calendar-to-add-image").click(function () {
                $("#calendar-to-add-image-type").modal('show');
            });
            $("#calendar-to-add-image-type-all").click(function () {
                showAddImage(true);
            });
            $("#calendar-to-add-image-type-one").click(function () {
                showAddImage(false);
            });
            $("#calendar-constructor-generate").click(function () {
                const self = $(this);
                spinner(self, true);
                calendarPrint(sheet.id, '#calendar-constructor-list-content .a4', function (res) {
                    spinner(self, false);
                });
            });
            $("#calendar-text-editor-add-confirmed").click(function () {
                if (selectedConcept) {
                    function addNodes(nodes) {
                        for (let i = 0; i < nodes.length; i++) {
                            if (nodes[i].nodes) {
                                addNodes(nodes[i].nodes);
                            } else {
                                addTextElement(nodes[i].text, $("#calendar-constructor-edit-size").val(), $("#calendar-constructor-edit-color").val());
                            }
                        }
                    }

                    if (selectedConcept.nodes) {
                        addNodes(selectedConcept.nodes);
                    }
                }
                $("#dialog-confirm-add-text").modal('hide');
            });
            $(document).click(function (e) {
                const els = $(".calendar-element");
                if (!els.is(e.target) && els.has(e.target).length === 0) {
                    selectedElement = false;
                    els.removeClass('selected');
                }
            });
            $("#letter-images-search")
                .on('input change', function () {
                    let alphabetImages = [];
                    if (calendarImages !== undefined && calendarImages.alphabet != undefined) {
                        alphabetImages = getCalendarAlphabetImages(calendarImages.alphabet, $(this).val());
                    }
                    const html = [];
                    for (let i = 0; i < alphabetImages.length; i++) {
                        html.push(renderCalendarConstructorImage(alphabetImages[i]));
                    }
                    $("#letter-images").html(html.length ? html.join("") : '<div class="empty">No images</div>');
                    eventsCalendarConstructorImages();
                });

            const monthsOptions = [];
            for (let i = 1; i <= 12; i++) {
                monthsOptions.push('<option value="' + i + '">' + getMonth(i) + '</option>');
            }
            $("#calendar-months").html(monthsOptions.join(""));
            const yearsOptions = [];
            const currentYear = (new Date).getFullYear();
            for (let i = 0; i <= 10; i++) {
                const year = currentYear + i;
                yearsOptions.push('<option value="' + year + '"' + (year === currentYear ? ' selected' : '') + '>' + year + '</option>');
            }
            $("#calendar-years").html(yearsOptions.join());

            tippy('#calendar-editor-helper', {
                content: `Select the item you want to add`,
                allowHTML: true,
            });
            tippy('#calendar-image-editor-helper', {
                content: `Select the category you want to add`,
                allowHTML: true,
            });
            tippy('.calendar-images-window-help', {
                content: `
            <div>When you click a picture, it is added to the calendar.</div>
            <div>The added image turns green for convenience.</div>
            <div>If desired, the same picture can be added several times.</div>
        `,
                allowHTML: true,
            });

            $("#name-constructor-name").on('input', function () {
                const self = $(this);
                const name = self.val().replace(/[^A-z]/g, '');
                if (name.length === 0) {
                    $("#name-constructor-list-content .a4").html('');
                    $("#name-constructor-generate, #name-constructor-print").attr('disabled', '');
                } else {
                    $("#name-constructor-print").attr('disabled', '');
                    $("#name-constructor-generate").removeAttr('disabled');
                }
            });
            $("#calendar-constructor-edit-size, #calendar-constructor-edit-color")
                .on('input change', function (e) {
                    const self = $(this);
                    const value = $(this).val();
                    calendarTextEditorPreview();
                });
        }

        if (user['allow'] === true) {
            $("#draft-btn").click();
        }
        if (user['allow'] === false) {
            $("#subscription-btn").click();
        }

        loader(false);
    }, function (res) {
        $("#signin").removeClass('d-none');
        title('Welcome');
        err(res);

        loader(false);
    });
}

function title(title) {
    document.title = `Teacher Plan Builder: ${title}`;
}

function spinner(el, mode) {
    const spinner = el.find('.spinner-border');
    if (mode) {
        spinner.removeClass('d-none');
    } else {
        spinner.addClass('d-none');
    }
}

function showNameConstructor() {
    $('.page').addClass('d-none');
    loader(true, $("#page-loader"), 'Download resources (<span id="id-da-prc">0%</span>)');
    title('Name constructor');
    let countAll = alphabet.length * 2;

    function downloadImage(src) {
        const img = new Image();
        img.onload = function () {
            countAll--;
            if (countAll === 0) {
                loader(false, $("#page-loader"));
                $("#name-constructor").removeClass('d-none');
                $("#name-constructor-name").val('').trigger("input");
                $('body').scrollTo('#name-constructor');
            }
            if (alphabet.length) {
                $("#id-da-prc").text(Math.floor(((alphabet.length * 2 - countAll) / (alphabet.length * 2)) * 100) + '%');
            }
        }
        img.onerror = function () {
            err('Error download resources. Please reload page');
        }
        img.src = src;
    }

    for (const id in alphabet) {
        const letter = alphabet[id];
        downloadImage('img/alphabet/' + letter + '.png?v=' + Version);
        downloadImage('img/alphabet/' + letter + '_.png?v=' + Version);
    }
}

function showDrafts() {
    loader(false, $("#page-loader"));
    $("#draft").removeClass('d-none');
    title('Calendar Drafts');
}

function showCalendarMonthSelector() {
    $("#calendar-months").val(1);
    $("#calendar-years").val((new Date).getFullYear());
    $("#calendar-month-selector").modal('show');
}

function showCalendarConstructor(mode, id, noShowPageCallback) {
    let i;
    if (noShowPageCallback === undefined) {
        $('.page').addClass('d-none');
        loader(true, $("#page-loader"), 'Download resources (<span id="id-da-prc">0%</span>)');
    }
    let month;
    let year;
    let loadData = false;
    if (typeof (mode) == "object") {
        loadData = mode;
        mode = loadData.id === undefined;
        if (loadData.id !== undefined) {
            id = loadData.id;
        }
    }
    if (mode === true) {
        CalendarElements = [];
        month = parseInt($("#calendar-months").val());
        year = parseInt($("#calendar-years").val());
        sheet = {
            data: {
                elements: [],
                calendar: {},
                month: month,
                year: year
            }
        };
    } else {
        sheet = sheets[id];
        month = sheet.data.month;
        year = sheet.data.year;
    }
    if (loadData !== false) {
        sheet.data = loadData.data;
        month = sheet.data.month;
        year = sheet.data.year;
    }
    const sheetMonthNameImg = 'img/months/' + getMonth(month) + '.png';
    let calendarPage = $("#calendar-constructor-list-content .a4");
    if (noShowPageCallback !== undefined) {
        $("#calendar-constructor-tmp").html('<div class="a4"></div>');
        calendarPage = $("#calendar-constructor-tmp .a4");
    }
    currentCalendarConstructorContainer = calendarPage;
    let html = `
        <table class="calendar-table-top">
            <tbody>
                <tr class="line">
                    <td class="line-edit"  item-id="text-1" colspan="7">
                        <span class="text"></span>
                        <input class="d-none" >
                    </td>
                </tr> 
                <tr class="line">
                    <td class="line-edit"  item-id="text-2" colspan="7">
                        <span class="text"></span>
                        <input class="d-none" >
                    </td>
                </tr>
            </tbody>
        </table>
        <table class="calendar-table">
            <thead>
                <tr>
                    <th colspan="7">
                        <img id="calendar-title-image" src="` + sheetMonthNameImg + `">
                    </th>
                </tr>
            </thead>
            <tbody id="calendar-table"></tbody>
        </table>
        <table class="calendar-table-ex">
            <thead>
                <tr>
                    <th>
                        <div class="th-hint line-edit" item-id="text-10">
                            <span class="text">Yesterday</span>
                            <input class="d-none">
                        </div>
                        <div class="text underline line-edit" item-id="text-13">
                            <span class="text"></span>
                            <input class="d-none">
                        </div>
                    </th>
                    <th>
                        <div class="th-hint line-edit" item-id="text-11">
                            <span class="text">Today</span>
                            <input class="d-none">
                        </div>
                        <div class="text underline line-edit" item-id="text-14">
                            <span class="text"></span>
                            <input class="d-none">
                        </div>
                    </th>
                    <th>
                        <div class="th-hint line-edit" item-id="text-12">
                            <span class="text">Tomorrow</span>
                            <input class="d-none">
                        </div>
                        <div class="text underline line-edit" item-id="text-15">
                            <span class="text"></span>
                            <input class="d-none">
                        </div>
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <div class="line-edit" item-id="text-16">
                            <span class="text"></span>
                            <textarea class="d-none"></textarea>
                        </div> 
                    </td>
                    <td>
                        <div class="line-edit" item-id="text-17">
                            <span class="text"></span>
                            <textarea class="d-none"></textarea>
                        </div> 
                    </td>
                    <td>
                        <div class="line-edit" item-id="text-18">
                            <span class="text"></span>
                            <textarea class="d-none"></textarea>
                        </div> 
                    </td>
                </tr>
            </tbody>
        </table>
        <table class="calendar-table-bottom">
            <tbody>
                <tr class="line">
                    <td style="height:44px;" class="line-edit" item-id="text-3" colspan="7">
                        <span class="text"></span>
                        <input class="d-none" >
                    </td>
                </tr>
            </tbody>
        </table>
        <div class="copyright">
            <span>http://www.teacherplanbuilder.com</span>
        </div>
    `;
    calendarPage.html(html);
    let date = new Date;
    const currentYear = year;
    const currentMonth = month - 1;
    date.setFullYear(currentYear);
    date.setMonth(currentMonth + 1);
    date.setDate(0);
    const currentMonthCountDays = date.getDate();
    date.setFullYear(currentYear);
    date.setMonth(currentMonth);
    date.setDate(0);
    const prevMonthCountDays = date.getDate();
    date = new Date;
    date.setFullYear(currentYear);
    date.setMonth(currentMonth);
    date.setDate(1);
    const weekDay = date.getDay();
    const calendarTable = calendarPage.find("#calendar-table");
    const weekType = $("#select-week-type").val();
    const week = weekType == 0 ? ["SUN", "MON", "TUE", "WED", "THU", "FRI", "SAT"] : ["MON", "TUE", "WED", "THU", "FRI", "SAT", "SUN"];
    weekHtml = [];
    for (i = 0; i < week.length; i++) {
        weekHtml.push('<td class="week-day">' + week[i] + '</td>');
    }
    html = `
        <tr>
            ` + weekHtml.join("") + `
        </tr>
    `;
    const start = weekDay > 0 ? (weekType === 1 ? 1 : 0) - weekDay : 0;
    for (i = 0; i < 5; i++) {
        html += '<tr>';
        let useGreen = false;
        for (let k = 1; k <= 7; k++) {
            let current = i * 7 + k + start;
            let disabled = false;
            const dayOff = k === 1 || k === 7;
            if (current > currentMonthCountDays) {
                current = current - currentMonthCountDays;
                disabled = true;
            }
            if (current <= 0) {
                current = prevMonthCountDays + current;
                disabled = true;
            }
            let cellColor = false;
            const cellKey = i + '_' + (k - 1);
            if (sheet.id !== undefined && sheet.data && sheet.data.calendar[cellKey] !== undefined) {
                cellColor = sheet.data.calendar[cellKey];
            }

            const itemId = ` item-id="` + (i * 7 + k) + `"`;
            const monthId = disabled ? '' : ' item-month-id="' + current + '"';
            const colorAttribute = cellColor ? ' color="' + cellColor + '" style="background: ' + cellColor + ';"' : '';

            useGreen = dayOff ? !useGreen : useGreen;
            let classDayOffColor = useGreen
                ? ' day-off-green'
                : ' day-off-yellow';

            classDayOffColor = colorAttribute === '' ? classDayOffColor : '';

            const classDayOff = dayOff ? ' day-off' + classDayOffColor : '';

            const classDisabled = disabled ? ' disabled' : '';
            const classEnabled = disabled ? '' : ' enabled';

            const classAttribute = ` class="` + (classDayOff) + (classDisabled) + (classEnabled) + `"`;
            html += `
                <td ` + (itemId) + (monthId) + (colorAttribute) + (classAttribute) + `>
                    <div class="calendar-td cube">
                        <span class="num">` + current + `</span>
                    </div>
                </td>
            `;
        }
        html += '</tr>';
    }
    calendarTable.html(html);
    calendarTable.find('.enabled').click(function () {
        const self = $(this);
        let color = self.attr('color');
        if (!color) {
            color = '#000000';
        }
        $("#calendar-day-color").val(color);
        $("#calendar-day-formatting").modal('show');
        selectDay = self;
    });
    $(".line-edit").click(function () {
        const self = $(this);
        self.find('input, textarea').val(self.text().trim().replace(/<br>/g, "\n")).removeClass('d-none').focus();
    }).find('input, textarea').blur(function () {
        const self = $(this);
        self.addClass('d-none').prev().html(self.val().replace(/\n/g, "<br>"));
        calendarAutoSave();
    });

    function getImages(obj) {
        const arr = [];
        for (const prop in obj) {
            const image = obj[prop];
            if (typeof (image) == "string") {
                arr.push(image)
            } else if (typeof (image) == "object") {
                const tmpArr = getImages(image);
                for (let i = 0; i < tmpArr.length; i++) {
                    arr.push(tmpArr[i]);
                }
            }
        }
        return arr;
    }

    const images = getImages(calendarImages);
    let countAll = images.length;
    let renderCompleted = false;

    function downloadImage(src) {
        const img = new Image();
        img.onload = function () {
            countAll--;
            if (countAll === 0) {
                const __checkRender = setInterval(function () {
                    if (renderCompleted) {
                        if (noShowPageCallback === undefined) {
                            loader(false, $("#page-loader"));
                            calendarAutoSave();
                            $("#calendar-constructor").removeClass('d-none');
                            $('body').scrollTo('#calendar-constructor');
                        }
                        clearInterval(__checkRender);
                        if (typeof (noShowPageCallback) == "function") {
                            noShowPageCallback.call(null, true);
                        }
                    }
                }, 100);
            }
            if (images.length) {
                $("#id-da-prc").text(Math.floor(((images.length - countAll) / images.length) * 100) + '%');
            }
        }
        img.onerror = function () {
            if (typeof (noShowPageCallback) == "function") {
                $("#calendar-constructor-tmp .a4").remove();
                noShowPageCallback.call(null, false);
            } else {
                err('Error download resources. Please reload page');
            }
        }
        img.src = src;
    }

    for (i = 0; i < countAll; i++) {
        downloadImage(images[i] + '?v=' + Version);
    }
    if (sheet.id !== undefined || loadData !== false) {
        CalendarElements = [];
        for (i = 0; i < sheet.data.elements.length; i++) {
            CalendarElements.push(sheet.data.elements[i]);
            renderElement(i);
        }
    }
    if (sheet.data.texts === undefined) {
        sheet.data.texts = {};
    }
    for (const itemId in sheet.data.texts) {
        $(".line-edit[item-id='" + itemId + "']").find('.text').html(sheet.data.texts[itemId]);
    }
    renderCompleted = true;
}

function calendarTextEditorPreview() {
    let size = parseInt($("#calendar-constructor-edit-size").val()) || 0;
    if (size < 8) {
        size = 8;
    }
    if (size > 100) {
        size = 100;
    }
    $("#calendar-constructor-edit-preview").css({
        fontSize: size + 'px',
        lineHeight: size + 'px',
        color: $("#calendar-constructor-edit-color").val()
    });
}

function addTextElement(text, size, color) {
    let x = -1;
    let y = -1;
    let cell = randCell(true);
    x = cell.x;
    y = cell.y;
    if (x === -1 && y === -1) {
        cell = randCell(false);
        x = cell.x;
        y = cell.y;
    }
    CalendarElements.push({
        type: 'text',
        x: x,
        y: y,
        w: -1,
        h: -1,
        text: text,
        size: size,
        color: color
    });
    const id = CalendarElements.length - 1;
    renderElement(id);
    selectElement(id);
}

function addImageElement(image, x, y, w, h) {
    x = typeof (x) == "number" ? x : -1;
    y = typeof (y) == "number" ? y : -1;
    w = typeof (w) == "number" ? w : -1;
    h = typeof (h) == "number" ? h : -1;
    const a = document.createElement('a');
    a.href = image;
    a.remove();
    CalendarElements.push({
        type: "image",
        x: x,
        y: y,
        w: w,
        h: h,
        image: a.pathname
    });
    const id = CalendarElements.length - 1;
    renderElement(id);
    selectElement(id);
    return id;
}

function editImageElement(id, image) {
    const element = CalendarElements[id];
    if (element !== undefined) {
        element.image = image;
        const a = document.createElement('a');
        a.href = image;
        a.remove();
        renderElement(id);
        selectElement(id);
    }
}

function selectElement(id) {
    const domElement = $("#calendar-element-" + id);
    if (domElement.length) {
        setTimeout(function () {
            if (selectedElement !== false) {
                $("#calendar-element-" + selectedElement).removeClass('selected').draggable('option', 'disabled', true).resizable('option', 'disabled', true);
            }
            domElement.addClass('selected').draggable('option', 'disabled', false).resizable('option', 'disabled', false);
            selectedElement = id;
        }, 100);
    }
}

function removeSelectedElement(e) {
    if (e.code !== "Delete" || e.altKey || e.ctrlKey || e.shiftKey) {
        return;
    }
    e.stopPropagation();
    setTimeout(function () {
        let wasRemoved = false;
        let lastIndex = selectedElement;
        if (selectedElement !== false) {
            removeElement(selectedElement);
            wasRemoved = true;
            selectedElement = false;
        }
        if (wasRemoved) {
            for (let k = lastIndex; k >= 0; --k) {
                const calendarElement = CalendarElements[k];
                if (calendarElement !== undefined) {
                    selectElement(k);
                    break;
                }
            }
        }
    }, 100);
}

function calendarAutoSave() {
    const calendarData = {
        id: sheet.id,
        data: getCalendarData()
    };
    localStorage.setItem('calendar-autosave', JSON.stringify(calendarData));
}

function renderElement(id) {
    if (CalendarElements[id] !== undefined) {
        const element = CalendarElements[id];
        const container = currentCalendarConstructorContainer;
        let domElement = container.find("#calendar-element-" + id);
        if (!domElement.length) {
            let elementHtml = '';
            if (element.type == 'text') {
                elementHtml = '<div class="text"></div>';
            } else if (element.type == 'image') {
                elementHtml = '<div class="image"></div>';
            }
            container.append(`
                <div id="calendar-element-` + id + `" class="calendar-element ` + element.type + `">
                    ` + elementHtml + `
                    <i class="calendar-element-remove bi bi-x-lg text-danger"></i>
                </div>
            `);
            domElement = container.find("#calendar-element-" + id);
            domElement.draggable({
                grid: [5, 5],
                containment: "#calendar-constructor-list-content",
                disabled: true,
                stop: function () {
                    const calendarElement = CalendarElements[id];
                    if (calendarElement !== undefined) {
                        calendarElement.x = domElement.position().left;
                        calendarElement.y = domElement.position().top;
                        calendarAutoSave();
                    }
                }
            }).resizable({
                handles: "n, e, s, w, ne, se, sw, nw",
                disabled: true,
                stop: function () {
                    const calendarElement = CalendarElements[id];
                    if (calendarElement !== undefined) {
                        calendarElement.w = domElement.width();
                        calendarElement.h = domElement.height();
                        calendarAutoSave();
                    }
                }
            }).click(function (e) {
                selectElement($(this).attr('item-id'));
            });
            domElement.attr('item-id', id).find('.calendar-element-remove').click(function (e) {
                e.stopPropagation();
                removeElement($(this).parent().attr('item-id'));
                return false;
            });
        }
        const elementPosInfo = {};
        if (element.x == -1) {
            element.x = (container.width() - domElement.width()) / 2;
        }
        if (element.y == -1) {
            element.y = (container.height() - domElement.height()) / 2;
        }
        elementPosInfo.left = element.x + 'px';
        elementPosInfo.top = element.y + 'px';
        if (element.w != -1) {
            elementPosInfo.width = element.w + 'px';
        }
        if (element.h != -1) {
            elementPosInfo.height = element.h + 'px';
        }
        domElement.css(elementPosInfo);
        if (element.type == 'text') {
            domElement.css({
                fontSize: element.size + 'px',
                lineHeight: element.size + 'px',
                color: element.color
            }).find('.text').html(element.text.replace("\n", '<br>'));
        } else if (element.type == 'image') {
            domElement.find('.image').html('<img src="' + HostClear + element.image + '?v=' + Version + '">');
        }
        calendarAutoSave();
    }
}

function removeElement(id) {
    const element = CalendarElements[id];
    if (element !== undefined) {
        const calendarElement = $("#calendar-element-" + id);
        if (calendarElement.length) {
            calendarElement.remove();
        }
        CalendarElements[id] = undefined;
        calendarAutoSave();
    }
}

function showAddText() {
    $("#calendar-constructor-edit-color").trigger('change');
    $("#calendar-text-editor").modal('show');
}

function getCalendarMonthImages(obj) {
    const arr = [];
    if (typeof (obj) == "object") {
        for (const prop in obj) {
            const image = obj[prop];
            if (typeof (image) == "string") {
                arr.push({
                    name: prop,
                    photo: image + '?v=' + Version
                });
            } else {
                const tmpArr = getCalendarMonthImages(image);
                for (let i = 0; i < tmpArr.length; i++) {
                    arr.push(tmpArr[i]);
                }
            }
        }
    }
    return arr;
}

function getCalendarAlphabetImages(obj, search) {
    const arr = [];
    if (typeof (obj) == "object") {
        for (const prop in obj) {
            const image = obj[prop];
            if (typeof (image) == "string") {
                if (typeof (search) == "string" && search.length && prop.toLowerCase().indexOf(search.toLowerCase()) == -1) {
                    continue;
                }
                arr.push({
                    name: prop,
                    photo: image
                });
            } else {
                const tmpArr = getCalendarAlphabetImages(image);
                for (let i = 0; i < tmpArr.length; i++) {
                    arr.push(tmpArr[i]);
                }
            }
        }
    }
    return arr;
}

function randCell(mode) {
    let x = -1;
    let y = -1;
    if (sheet) {
        const table = $('.calendar-table');
        const tds = mode === true ? $('.calendar-table td[item-month-id]') : $('.calendar-table td[item-id]');
        const emptyPositions = [];
        tds.each(function (i, e) {
            const td = $(e);
            const tdy = table.position().top + td.position().top + 1;
            const tdx = table.position().left + td.position().left + 1;
            const w = td.width();
            const h = td.height();
            let empty = true;
            for (let k = 0; k < CalendarElements.length; k++) {
                const calendarElement = CalendarElements[k];
                if (
                    calendarElement !== undefined
                    && calendarElement.x >= tdx
                    && calendarElement.x <= tdx + w
                    && calendarElement.y >= tdy
                    && calendarElement.y <= tdy + h) {
                    empty = false;
                }
            }
            if (empty) {
                emptyPositions.push({
                    x: tdx,
                    y: tdy
                });
            }
        });
        const position = emptyPositions[rand(0, emptyPositions.length - 1)];
        if (typeof (position) == "object") {
            x = position.x;
            y = position.y;
        }
    }
    return {
        x: x,
        y: y
    };
}

function showAddImage(mode) {
    let i;
    let imageId = -1;
    if (typeof (mode) == "number") {
        imageId = mode;
        mode = false;
    }
    const month = getMonth(sheet.data.month);
    let monthImages = [];
    if (
        calendarImages !== undefined
        && calendarImages.images !== undefined
        && calendarImages.images[month] !== undefined
    ) {
        monthImages = getCalendarMonthImages(calendarImages.images[month]);
    }
    let html = [];
    for (i = 0; i < monthImages.length; i++) {
        if (mode === true) {
            let y = Math.floor(i / 7);
            let x = i - (y * 7);
            x = 35 + (x * 100);
            y = 65 + (y * 100);
            const cell = randCell();
            y = cell.y;
            x = cell.x;
            addImageElement(monthImages[i].photo, x, y);
        } else {
            html.push(renderCalendarConstructorImage(monthImages[i]));
        }
    }
    if (mode === true) {
        return true;
    }
    $("#month-images").html(html.length ? html.join("") : '<div class="empty">No images</div>');
    let alphabetImages = [];
    if (calendarImages !== undefined && calendarImages.alphabet != undefined) {
        alphabetImages = getCalendarAlphabetImages(calendarImages.alphabet);
    }
    html = [];
    for (i = 0; i < alphabetImages.length; i++) {
        html.push(renderCalendarConstructorImage(alphabetImages[i]));
    }
    $("#letter-images").html(html.length ? html.join("") : '<div class="empty">No images</div>');
    eventsCalendarConstructorImages(imageId);
    if (imageId >= 0) {
        const image = CalendarElements[imageId];
        if (image !== undefined) {
            $("#calendar-images-window").find('[src="' + image.image + '"]').parent().parent().addClass('selected');
        }
    }
    $("#calendar-images-window").modal('show');
}

function getFilesOfAllCategories(imagesWithCategories) {

    return imagesWithCategories["categories"] ?? [];
}

function addCategoryImages(category) {
    let i;

    let monthImages = [];

    function getCategoryImages(category, allImages) {

        let categoryNodes = [];
        for (let key in allImages) {
            if (key === category) {
                categoryNodes.push(allImages[key]);
                break;
            }
            if (typeof (allImages[key]) === "object") {
                const child = allImages[key];
                const nodes = getCategoryImages(category, child);

                if (nodes.length > 0) {
                    categoryNodes = nodes;
                    break;
                }
            }
        }

        return categoryNodes;
    }

    if (
        calendarImages !== undefined
    ) {
        const filesOfAllCategories = getFilesOfAllCategories(
            calendarImages
        );
        const images = getCategoryImages(
            category,
            filesOfAllCategories,
            );
        monthImages = getCalendarMonthImages(images);
    }
    for (i = 0; i < monthImages.length; i++) {
        let y = Math.floor(i / 7);
        let x = i - (y * 7);
        x = 35 + (x * 100);
        y = 65 + (y * 100);
        const cell = randCell();
        y = cell.y;
        x = cell.x;
        addImageElement(monthImages[i].photo, x, y);
    }

    return true;
}

function getCalendarData() {
    const data = {};
    data.elements = [];
    for (let i = 0; i < CalendarElements.length; i++) {
        const el = CalendarElements[i];
        if (el !== undefined) {
            data.elements.push(el);
        }
    }
    data.calendar = {};
    $("#calendar-table tr").each(function (i, e) {
        const tr = $(e);
        tr.find('td.enabled').each(function (i, e) {
            const td = $(e);
            const color = td.attr('color');
            if (typeof (color) == "string" && color.length) {
                data.calendar[(tr.index() - 1) + '_' + td.index()] = color;
            }
        });
    });
    data.month = sheet.data.month;
    data.year = sheet.data.year;
    data.texts = {};
    $('.line-edit[item-id]').each(function (i, e) {
        const el = $(e);
        data.texts[el.attr('item-id')] = el.find('.text').html();
    });
    return data;
}

function calendarPrint(id, list, callback) {
    html2canvas(document.querySelector(list), {
        allowTaint: true,
        useCORS: true,
        dpi: 300,
        scale: 5
    }).then(canvas => {
        const doc = new jspdf.jsPDF({
            orientation: 'p',
            unit: 'mm',
            format: 'a4'
        });
        canvas.webkitImageSmoothingEnabled = true;
        canvas.mozImageSmoothingEnabled = true;
        canvas.imageSmoothingEnabled = true;
        doc.addImage(canvas.toDataURL('image/png'), 'PNG', 0, 0, doc.internal.pageSize.width, doc.internal.pageSize.height);
        doc.autoPrint();
        window.open(doc.output('bloburl'), '_blank');
        if (typeof (callback) == "function") {
            callback.call(null, true);
        }
    });
}

$(document).ready(function () {
    const storedEmail = localStorage.getItem("rememberMe");
    if (storedEmail !== null) {
        $("#signin-email").val(storedEmail);
    }

    window.addEventListener(
        "keyup",
        function (event) {
            removeSelectedElement(event);
        }
    );

    $(".user-avatar img")
        .attr('src', 'img/avatar.png?v=' + Version);

    if ($("#verify").hasClass('d-none')) {
        let storageToken = localStorage.getItem('token');
        if (typeof (storageToken) === "string" && storageToken.length) {
            userToken = storageToken;
        }
        if (userToken) {
            getUserProfile();
        }
        if (!userToken) {
            showSignInForm();
        }
    }

    if (
        guestMenuWasInitialized !== undefined
        && guestMenuWasInitialized === true
    ) {
        return;
    }
    guestMenuWasInitialized = true;

    $("#btn-signin").click(function () {
        const email = $("#signin-email").val().trim();
        const rememberMe =
            document.getElementById("rememberMe").checked;
        if (rememberMe) {
            localStorage.setItem("rememberMe", email);
        }
        if (!rememberMe) {
            localStorage.removeItem("rememberMe");
        }

        const self = $(this);
        spinner(self, true);
        api('signin', {
            email: email,
            password: aesEncrypt($("#signin-password").val())
        }, function (res) {
            userToken = res;
            localStorage.setItem('token', userToken);
            spinner(self, false);
            getUserProfile();
        }, function (res) {
            spinner(self, false);
            err(res, "#signin-result");
        });
    });
    $(".to-signup").click(function () {
        $("#signin, #verify, #panel").addClass('d-none');
        $("#signup").removeClass('d-none');
        title('Sign up');
    });
    $(".to-signin").click(function () {
        $("#signup, #verify, #panel").addClass('d-none');
        $("#signin").removeClass('d-none');
        title('Sign in');
    });
    $("#signup-btn").click(function () {
        const self = $(this);
        spinner(self, true);
        api('signup', {
            email: $("#signup-email").val().trim(),
            password: aesEncrypt($("#signup-password").val()),
            name: $("#signup-name").val().trim()
        }, function (res) {
            spinner(self, false);
            suc(res, "#signup-result");
        }, function (res) {
            spinner(self, false);
            err(res, "#signup-result");
        });
    });
    $("#to-forgot").click(function () {
        $("#forgot-btn").removeClass('d-none');
    })
    $("#forgot-btn").click(function () {
        const self = $(this);
        spinner(self, true);
        api('recovery', {
            email: $("#signin-email").val().trim()
        }, function (res) {
            suc(res, "#signin-result");
            spinner(self, false);
            self.addClass('d-none');
        }, function (res) {
            spinner(self, false);
            err(res, "#signin-result");
        });
    });
    $("#signout-btn").click(function () {
        const self = $(this);
        spinner(self, true);
        api('signout', function (res) {
            spinner(self, true);
            localStorage.removeItem('token');
            userToken = '';
            $("#panel").addClass('d-none');
            $("#signin").removeClass('d-none');
        }, function (res) {
            spinner(self, false);
            err(res);
        });
    });
    $("#menu-signout-btn").click(function () {
        const self = $(this);
        spinner(self, true);
        api('signout', function (res) {
            spinner(self, false);
            localStorage.removeItem('token');
            userToken = '';
            $("#panel").addClass('d-none');
            $("#signin").removeClass('d-none');
        }, function (res) {
            spinner(self, false);
            err(res);
        });
    });
    $("#settings-btn").click(function () {
        $('.page').addClass('d-none');
        $('#settings').removeClass('d-none');
        title('Settings');
    });
    $("#subscription-btn").click(function () {
        $('.page').addClass('d-none');
        $('#subscription').removeClass('d-none');
        title('Subscription');
    });
    $("#save-general-btn").click(function () {
        const self = $(this);
        const name = $("#settings-name").val();
        const surname = $("#settings-surname").val();
        const interests = $("#settings-interests").val();
        const login = $("#settings-login").val();
        const about = $("#settings-about").val();
        const gender = $("#settings-gender").val();
        const week = parseInt($("#select-week-type").val());
        spinner(self, true);
        try {
            api('setProfile', {
                name: name,
                surname: surname,
                interests: interests,
                login: login,
                about: about,
                gender: gender,
                week_type: week,
            }, function (res) {
                suc(res, "#save-general-result");
            }, function (res) {
                err(res, "#save-general-result");
            });
        } finally {
            spinner(self, false);
        }
    });
    $("#settings-avatar-upload-btn").click(function () {
        const self = $(this);
        upload(function (res) {
            const name = res.name;
            const photo = res.photo;
            api('profileSaveAvatar', {
                photo: name
            }, function (res) {
                user.photo = res;
                const avatar = $("#user-avatar");
                const settingsAvatar = $("#settings-user-avatar");
                spinner(self, false);
                avatar.addClass('d-none');
                settingsAvatar.addClass('d-none');
                spinner(avatar.parent(), true);
                spinner(settingsAvatar.parent(), true);
                avatar.get(0).src = res;
                settingsAvatar.get(0).src = res;
                $("#settings-avatar-remove-btn").removeClass('d-none');
                suc('Avatar saved', '#settings-avatar-result');
            }, function (res) {
                spinner(self, false);
                err(res, "#settings-avatar-result");
            });
        }, function (res) {
            spinner(self, false);
            err(res, "#settings-avatar-result");
        }, function () {
            spinner(self, true);
        });
    });
    $("#settings-avatar-remove-btn").click(function () {
        const self = $(this);
        spinner(self, true);
        api('profileRemoveAvatar', function (res) {
            spinner(self, false);
            self.addClass('d-none');
            suc(res, '#settings-avatar-result');
            $("#user-avatar, #settings-user-avatar").attr('src', 'img/avatar.png?v=' + Version);
        }, function (res) {
            spinner(self, false);
            err(res, '#settings-avatar-result');
        });
    });
    $("#settings-password-btn").click(function () {
        const self = $(this);
        const password = $("#settings-password").val();
        const rpassword = $("#settings-rpassword").val();
        if (password !== rpassword) {
            err('Password mismatch', '#settings-password-result');
        } else {
            spinner(self, true);
            api('changePassword', {
                password: aesEncrypt(password)
            }, function (res) {
                spinner(self, false);
                suc(res, '#settings-password-result');
            }, function (res) {
                spinner(self, false);
                err(res, '#settings-password-result');
            });
        }
    });
    $("#settings-email-btn").click(function () {
        const self = $(this);
        const codeInput = $("#settings-email-code");
        const email = $("#settings-email").val();
        const code = $("#settings-email-code").val();
        spinner(self, true);
        if (codeInput.hasClass('d-none')) {
            api('changeEmailCode', {
                email: email
            }, function (res) {
                spinner(self, false);
                suc(res, '#settings-email-result');
                codeInput.removeClass('d-none').prev().removeClass('d-none');
            }, function (res) {
                spinner(self, false);
                err(res, '#settings-email-result');
            });
        } else {
            api('changeEmail', {
                email: email,
                code: code
            }, function (res) {
                spinner(self, false);
                suc(res, '#settings-email-result');
                codeInput.addClass('d-none').prev().addClass('d-none');
                user.email = email;
            }, function (res) {
                spinner(self, false);
                err(res, '#settings-email-result');
            });
        }
    });

    $(".user-avatar img")
        .on('load', function () {
            const self = $(this);
            self.prev().addClass('d-none');
            self.removeClass('d-none');
        });
    $(".user-avatar img")
        .on('error', function () {
            const self = $(this);
            self.prev().addClass('d-none');
            self.addClass('d-none');
        });
});