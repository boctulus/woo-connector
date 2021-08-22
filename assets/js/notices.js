function insertAfter(referenceNode, newNode) {
    referenceNode.parentNode.insertBefore(newNode, referenceNode.nextSibling);
}

/*
    Se puede especificar el contenedor o after pero no ambos al mismo tiempo y ambos son opcionales.
*/
function addWpNotice(message, type = 'info', container = '#alert_container', after = null, replace = false){
    let types = ['info', 'danger', 'warning', 'success', 'error', 'done'];

    if (container == null && after == null){
        container = '#alert_container';
    }

    if (jQuery.inArray(type, types) == -1){
        throw "Tipo de notificación inválida para " + type;
    }

    if (type == 'danger'){
        type = 'warning';
    }

    if (type == 'done'){
        type = 'success';
    }

    if (message === ""){
        throw "Mensaje de notificación no puede quedar vacio";
    }

    if (container != null){
        if (container[0] != '#' && container[0] != '.'){
            container = '#' + container;
        }
   
        let alert_container  = document.querySelector(container);
    }
    
    let after_elem = document.querySelector(after);

    if (replace){
        if (container != null){
            alert_container.innerHTML = '';
        }         
    }

    let code = (new Date().getTime()).toString();
    let id_notice = "notice-" + code;

    div = document.createElement('div');
    
    div.innerHTML = `
    <div class="notice notice-${type} is-dismissible" id="${id_notice}" style="min-height:30px">
        <p>${message}</p>
    </div>`;

    
    if (after_elem == null){
        alert_container.prepend(div);
    } else {
        insertAfter(after_elem, div);
    } 
    

    return id_notice;
}

function addWpAdminNotice(message, type = 'info', replace = false){
    return addWpNotice(message, type, null,  'h1', replace)
}

function addNotice(message, type = 'info', container = '#alert_container', replace = false){
    let types = ['info', 'danger', 'warning', 'success'];

    if (jQuery.inArray(type, types) == -1){
        throw "Tipo de notificación inválida para " + type;
    }

    if (message === ""){
        throw "Mensaje de notificación no puede quedar vacio";
    }

    if (container[0] != '#' && container[0] != '.'){
        container = '#' + container;
    }

    let alert_container  = document.querySelector(container);

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

function hideNotice(container = '#alert_container', notice_id = null){
    if (notice_id == null){
        if (container[0] != '#' && container[0] != '.'){
            container = '#' + container;
        }

        document.querySelector(container).innerHTML = '';
    } else {
        document.getElementById(notice_id).remove();
    }
}
