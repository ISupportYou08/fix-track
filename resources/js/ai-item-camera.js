document.addEventListener('alpine:init', () => {
    window.Alpine.data('aiItemCamera', () => ({
        active: false,
        error: '',
        stream: null,

        async startCamera() {
            if (this.active) {
                return;
            }

            this.error = '';

            if (!navigator.mediaDevices?.getUserMedia) {
                this.error = 'Camera access is not supported by this browser. Use Upload images instead.';

                return;
            }

            try {
                this.stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: { ideal: 'environment' } },
                    audio: false,
                });
                this.active = true;

                await this.$nextTick();
                this.$refs.cameraVideo.srcObject = this.stream;
                await this.$refs.cameraVideo.play();
            } catch (error) {
                this.stopCamera();
                this.error = error?.name === 'NotAllowedError'
                    ? 'Camera permission was denied. Allow camera access in your browser or upload an image.'
                    : 'The camera could not be opened. Check that a camera is connected or upload an image.';
            }
        },

        async capturePhoto() {
            const video = this.$refs.cameraVideo;
            const canvas = this.$refs.cameraCanvas;

            if (!video?.videoWidth || !video?.videoHeight || !canvas) {
                this.error = 'The camera is still starting. Wait a moment and try again.';

                return;
            }

            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);

            const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.9));

            if (!blob || typeof DataTransfer === 'undefined') {
                this.error = 'The photo could not be captured. Use Upload images instead.';

                return;
            }

            const transfer = new DataTransfer();
            transfer.items.add(new File([blob], `fixtrack-item-${Date.now()}.jpg`, { type: 'image/jpeg' }));
            this.$refs.uploadInput.files = transfer.files;
            this.stopCamera();
            this.$refs.uploadInput.dispatchEvent(new Event('change', { bubbles: true }));
        },

        stopCamera() {
            this.stream?.getTracks().forEach((track) => track.stop());
            this.stream = null;
            this.active = false;

            if (this.$refs.cameraVideo) {
                this.$refs.cameraVideo.srcObject = null;
            }
        },

        destroy() {
            this.stopCamera();
        },
    }));
});
