$(function () {

    $('.custom-file-input').on('change', function (event) {
        let inputFile = event.currentTarget;
        $(inputFile).parent()
            .find('.custom-file-label')
            .html(inputFile.files[0].name);
        let fileXML = $("#file_form_xmlFile")[0].files[0];
        //console.log(fileXML);
        let formData = new FormData();
        formData.append("xmlFile", fileXML);
        sendFile(formData);

        //let formData = new FormData(document.forms.exportExcel);
        /*        let formData = new FormData();

                formData.append("exportExcel[choose_excel_file]", fileExcel);*/

        /*        let elements = document.querySelectorAll("select[name^='columnName']");*/
        /*        for(let i = 0 ; i < elements.length ; i++){
                    let item = elements.item(i);
                    formData.append("columnName[]", item.value);
                }*/
        /*       console.log(formData);*/

    });

    function sendFile(data) {
        $.ajax({
            url: '/',
            method: 'POST',
            data: data,
            dataType: 'json',
            processData: false,
            contentType: false,
            success: function (data) {
                console.log(JSON.parse(data));
                visualizeXML(data);

                /*                if (data.errors.length > 0) {
                                    $('.alert').remove();
                                    makeErrorsBlock(data);
                                } else {
                                    $('.alert').remove();
                                    makeTable(data);
                                }*/
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.log('fail');
            },
        })
    }

    function visualizeXML(data) {
        // Get DOM-element for inserting json-tree
        let wrapper = document.getElementById("wrapper");
        // Get json-data by javascript-object
/*        let data2 = {
            "firstName": "Jonh",
            "lastName": "Smith",
            "phones": [
                "123-45-67",
                "987-65-43"
            ]
        };*/

        // Create json-tree
        let tree = jsonTree.create(JSON.parse(data), wrapper);

        // Expand all (or selected) child nodes of root (optional)
        tree.expand(function (node) {
            return node.childNodes.length < 2 || node.label === 'phoneNumbers';
        });

    }


});