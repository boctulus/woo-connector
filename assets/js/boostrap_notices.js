function addNotice(message, type = 'info', id_container = 'alert_container', replace = false){
    let types = ['info', 'danger', 'warning', 'success'];

    if (jQuery.inArray(type, types) == -1){
        throw "Tipo de notificación inválida para " + type;
    }

    if (message === ""){
        throw "Mensaje de notificación no puede quedar vacio";
        return;
    }

    let alert_container  = document.getElementById(id_container);

    if (replace){
        alert_container.innerHTML = '';
    }

    let code = (new Date().getTime()).toString();
    let id_notice = "notice-" + code;
    let id_close  = "close-"  + code;

    div = document.createElement('div');			
    div.innerHTML = `
    <div class="alert alert-${type} alert-dismissible fade show mt-3" role="alert" id="${id_notice}">
        <span>
            ${message}
        </span>
        <button type="button" class="btn-close notice" data-bs-dismiss="alert" aria-label="Close" id="${id_close}"></button>
    </div>`;

    alert_container.classList.add('mt-5');
    alert_container.prepend(div);

    document.getElementById(id_close).addEventListener('click', () => {
        let cnt = document.querySelectorAll('button.btn-close.notice').length -1;
        if (cnt == 0){
            alert_container.classList.remove('mt-5');
            alert_container.classList.add('mt-3');
        }
    });


    return id_notice;
}

function hideNotice(id_container = 'alert_container', notice_id = null){
    if (notice_id == null){
        let div  = document.querySelector(`div#${id_container}`);
        div.innerHTML = '';
        alert_container.classList.remove('mt-5');
    } else {
        document.getElementById(notice_id).remove();
    }
}
