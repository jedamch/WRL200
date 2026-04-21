
let cropper;
const fileInput = document.getElementById("uploadImage");
const preview = document.getElementById("imageToCrop");
const cropBtn = document.getElementById("cropBtn");

fileInput.addEventListener("change", function (e) {
    const file = e.target.files[0];
    if (!file) return;

    preview.src = URL.createObjectURL(file);
    preview.style.display = "block";

    if (cropper) cropper.destroy();

    cropper = new Cropper(preview, {
        aspectRatio: 1,
        viewMode: 1
    });

    cropBtn.style.display = "inline-block";
});


cropBtn.addEventListener("click", function () {
    const canvas = cropper.getCroppedCanvas({
        width: 300,
        height: 300
    });

    canvas.toBlob(function(blob) {
        const reader = new FileReader();
        reader.onloadend = function() {
            document.getElementById("croppedImageInput").value = reader.result;
            document.getElementById("cropForm").submit();
        }
        reader.readAsDataURL(blob);
    });
});
