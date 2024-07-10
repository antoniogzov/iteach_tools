$(document).ready(function () {
  $(document).on("change", "#slct-lvl-combination", function () {
    //--- --- ---//
    loading();
    let id_level_combination = $(this).val();
    $.ajax({
      url: "php/controllers/structureUpdateController.php",
      method: "POST",
      data: {
        mod: "getPeriodsCalendar",
        id_level_combination: id_level_combination,
      },
    })
      .done(function (data) {
        Swal.close();
        console.log(data);
        var data = JSON.parse(data);
        if (data.response) {
          $("#slct-period").html(data.options);
          Swal.close();
          //--- --- ---//
        } else {
          Swal.fire("Atención!", data.message, "info");
        }
      })
      .fail(function (message) {
        VanillaToasts.create({
          title: "Error",
          text: "Ocurrió un error, intentelo nuevamente",
          type: "error",
          timeout: 1200,
          positionClass: "topRight",
        });
      });
  });

  $(document).on("change", "#slct-period", function () {
    var url = window.location.search;
    const urlParams = new URLSearchParams(url);
    let id_level_combination = $("#slct-lvl-combination").val();
    let id_period_calendar = $(this).val();

    if (urlParams.has("submodule")) {
      //--- --- ---//
      loading();
      const submodule = urlParams.get("submodule");
      window.location.search =
        "submodule=" +
        submodule +
        "&id_level_combination=" +
        id_level_combination +
        "&id_period_calendar=" +
        id_period_calendar;
      //--- --- ---//
    }
  });

  /*  $(document).on("click", ".btnUpdateStructure", function () {
    let id_assignment = $(this).attr("data-id-assignment");

    console.log(id_assignment);
  }); */

  $(document).on("click", ".btnUpdateStructure", function () {
    //--- --- ---//
    var id_assignment = $(this).attr("data-id-assignment");
    var id_period_calendar = $(this).attr("data-id-period-calendar");
    var element = $(this);
    Swal.fire({
      title: "Atención",
      text: "Este proceso podría demorar hasta 10 minutos en ejecutarse, y no debe actualizar la página ni interrumpir hasta que el proceso finalice. ¿Desea comenzar el proceso?",
      icon: "info",
      showCancelButton: true,
      confirmButtonColor: "#3085d6",
      cancelButtonColor: "#d33",
      confirmButtonText: "Continuar",
      cancelButtonText: "Cancelar",
    }).then((result) => {
      if (result.isConfirmed) {
        loading();
        $.ajax({
          url: "php/controllers/structureUpdateController.php",
          method: "POST",
          data: {
            mod: "createStructureQualificationsMassive",
            id_assignment: id_assignment,
            id_period_calendar: id_period_calendar,
          },
        })
          .done(function (data) {
            Swal.close();
            console.log(data);
            var data = JSON.parse(data);
            if (data.response) {
              Swal.fire("Hecho", data.message, "success").then((result) => {
                element.closest("tr").remove();
              });
              //--- --- ---//
            } else {
              Swal.fire("Atención!", data.message, "info");
            }
          })
          .fail(function (message) {
            Swal.fire("Atención", "Ocurrió un error en el proceso", "error");
          });
      }
    });
  });
  $(document).on("keypress", "#searchAssignment", function (event) {
    var id_period_calendar = $("#slct-period").val();
    //--- --- ---//
    var keycode = event.keyCode || event.which;
    if (keycode == 13) {
      /* alert("Enter!"); */
      event.preventDefault();
      loading();
      var search_value = $(this).val().trim();
      $.ajax({
        url: "php/controllers/structureUpdateController.php",
        method: "POST",
        data: {
          mod: "getAssignment",
          search_value: search_value,
        },
      })
        .done(function (info) {
          info = $.parseJSON(info);
          Swal.close();
          $("#tableAssignments > tbody").html(info.html);
          Swal.close();
        })
        .fail(function (message) {});
    }
  });
});

function loading() {
  Swal.fire({
    title: "Cargando...",
    html: '<img src="images/loading_tool.gif" width="300" height="300">',
    allowOutsideClick: false,
    allowEscapeKey: false,
    showCloseButton: false,
    showCancelButton: false,
    showConfirmButton: false,
  });
}
