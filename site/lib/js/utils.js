// {class: "", delay: 5000, msg: "", ret: false}
function uiMessage(data) {
    if (data.fn !== undefined) {
        $('#divToast').off('hidden.bs.toast').on('hidden.bs.toast', function () {
            doFunction(data.fn);
        });
    }
    $("#tHead").removeClass().addClass("me-auto").addClass("text-" + (data.class ? data.class : "info"));
    $("#tTitle").html(data.title);
    $("#tMsg").html(data.msg);
    $("#divToast").toast({ delay: data.delay ? data.delay : 5000 }).toast("show");
    return (data.ret !== undefined ? data.ret : false);
}

function getFormData(frm){
    var unindexed_array = $(frm).serializeArray();
    var indexed_array = {};
    $.map(unindexed_array, function(n, i){
        indexed_array[n['name']] = n['value'];
    });
    return indexed_array;
}

function goUrl(url, blank) {
    if (blank !== undefined ? blank : false) {
        window.open(url);
    } else {
        window.location.href = url;
    }
}

function doFunction(fn) {
    var vfn = window[fn.name]; /*global fn*/
    if (typeof vfn === 'function') {
        vfn.apply(window, fn.params);
        return fn.ret;
    }
    return false;
}

function getDefault(v, d) {
    return (v !== undefined ? v : d);
}

function okData(data, okforward) {
    if (data.msgbox !== undefined) {
        return msgBox(data.msgbox);
    }
    if (data.uimsg !== undefined) {
        return uiMessage(data.uimsg);
    }
    if (data.fn !== undefined) {
        return doFunction(data.fn);
    } 
    if (data.error !== undefined) {
        return uiMessage({ msg: data.error, title: "Error", class: "danger", ret: false });
    }
    return true;
}

function editForm(params) {
    var dlg = $("#divDlg");
    // Esta diferencia se hace según de dónde se llame.  
    if ('srvparams' in params && 'srvlet' in params.srvparams) {
        jsrv = params.srvparams.srvlet + "/jresp.php";
        hsrv = params.srvparams.srvlet + "/hresp.php";
    } else {
        jsrv = "jresp.php";
        hsrv = "hresp.php";
    }
    dlg.load(hsrv, params.params, function() {
        const dlgModal = new bootstrap.Modal('#divDlg');
        var frm = $(params.frmId);
        var form = document.getElementById(frm.attr("id"));
        form.addEventListener("submit", function (e) {
            form.classList.add('was-validated');
            event.preventDefault();
            event.stopPropagation();
            //console.log("CV: " + form.checkValidity());
            if (form.checkValidity()) {
                var sendData;
                if (params.fnFormData !== undefined) {
                    sendData = params.fnFormData();
                } else {
                    sendData = getFormData(params.frmId);
                }
                var saveparams = $.extend(true, params.srvparams, sendData);
                $.post(jsrv, saveparams, function (data) {
                    if (okData(data)) {
                        if (params.fnUpdate !== undefined) {
                            params.fnUpdate(data);
                        }
                        dlgModal.hide();
                    }
                }, "json");
            }
        });
        if (params.fnDialog !== undefined) {
            params.fnDialog();
        }
        dlgModal.show();
    });
}
