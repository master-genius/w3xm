function wstg_setItem(key,val, json_seri=false){
    if (json_seri) {
        sessionStorage.setItem(key,JSON.stringify(val));
    } else {
        sessionStorage.setItem(key, val);
    }
}

function wstg_getItem(key, json_seri=false){
    if (sessionStorage.getItem(key)===null) {
        return null;
    }
    if (json_seri) {
        return JSON.parse(sessionStorage.getItem(key));
    } else {
        return sessionStorage.getItem(key);        
    }
}

function wstg_removeItem(key){
    sessionStorage.removeItem(key);
}

function wstg_clear(){
    sessionStorage.clear();
}

function wstg_has(key) {
    if (sessionStorage.getItem(key) === null) {
        return true;
    }
    return false;
}

function make_group_options(group_list) {
    var html = '';
    for(var i=0; i<group_list.length; i++) {
        html += `
            <option value="${group_list[i].id}">
                ${group_list[i].group_name}
            </option>
        `;
    }
    
    return html;
}
