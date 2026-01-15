/**
 * MediaPipe Pose Measurement System
 * Captures body measurements using webcam and AI pose detection
 * WITH HEIGHT CALIBRATION for improved accuracy
 */

class MediaPipeMeasurement {
    constructor() {
        this.pose = null;
        this.camera = null;
        this.videoElement = null;
        this.canvasElement = null;
        this.canvasCtx = null;
        this.isInitialized = false;
        this.currentMeasurements = {};
        this.isCalibrated = false;
        this.frameCount = 0;

        // USER HEIGHT CALIBRATION (key for accuracy)
        this.userHeightInches = 66; // Default 5'6" - will be set by user
        this.detectedBodyHeightPixels = null;
        this.pixelsPerInch = null;
        this.calibrationFrames = [];
        this.calibrationComplete = false;

        // Measurement smoothing buffers
        this.measurementBuffers = new Map();
        this.bufferSize = 10;

        // Distance tracking
        this.optimalBodyHeightRatio = 0.75; // Body should be 75% of frame height
        this.distanceStatus = 'unknown';

        // Landmark indices (MediaPipe Pose)
        this.LANDMARKS = {
            nose: 0,
            leftEye: 2,
            rightEye: 5,
            leftShoulder: 11,
            rightShoulder: 12,
            leftElbow: 13,
            rightElbow: 14,
            leftWrist: 15,
            rightWrist: 16,
            leftHip: 23,
            rightHip: 24,
            leftKnee: 25,
            rightKnee: 26,
            leftAnkle: 27,
            rightAnkle: 28
        };

        // Valid measurement ranges (in inches) - anthropometric data
        this.MEASUREMENT_RANGES = {
            shoulder: { min: 13, max: 22 },      // Biacromial breadth
            chest: { min: 28, max: 58 },         // Chest circumference
            waist: { min: 22, max: 52 },         // Waist circumference
            hip: { min: 30, max: 56 },           // Hip circumference
            neck: { min: 12, max: 20 },          // Neck circumference
            sleeve_length: { min: 18, max: 38 }, // Shoulder to wrist
            kameez_length: { min: 22, max: 44 }, // Shoulder to knee
            trouser_length: { min: 32, max: 50 } // Waist to ankle
        };

        // Body proportion ratios (anthropometric research)
        this.BODY_RATIOS = {
            chestToShoulder: 2.3,    // Chest circumference ≈ 2.3x shoulder width
            waistToHip: 0.8,         // Waist ≈ 80% of hip
            neckToShoulder: 0.85,    // Neck ≈ 85% of shoulder width
            hipMultiplier: 2.4       // Hip circumference from hip width
        };
    }

    // Set user height for calibration
    setUserHeight(heightInches) {
        this.userHeightInches = heightInches;
        console.log(`User height set to: ${heightInches} inches (${Math.floor(heightInches/12)}'${heightInches%12}")`);
    }

    async initialize() {
        try {
            this.pose = new Pose({
                locateFile: (file) => {
                    return `https://cdn.jsdelivr.net/npm/@mediapipe/pose/${file}`;
                }
            });

            this.pose.setOptions({
                modelComplexity: 2, // Highest accuracy (0, 1, or 2)
                smoothLandmarks: true,
                enableSegmentation: false,
                smoothSegmentation: false,
                minDetectionConfidence: 0.7,
                minTrackingConfidence: 0.6
            });

            this.pose.onResults(this.onResults.bind(this));
            this.isInitialized = true;
            console.log('MediaPipe initialized with high accuracy model');

            return true;
        } catch (error) {
            console.error('MediaPipe initialization failed:', error);
            throw error;
        }
    }

    async startCamera(videoElementId) {
        try {
            this.stopCamera();

            this.videoElement = document.getElementById(videoElementId);
            if (!this.videoElement) {
                throw new Error(`Video element '${videoElementId}' not found`);
            }

            const stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    width: { ideal: 640 },
                    height: { ideal: 480 },
                    facingMode: 'user',
                    frameRate: { ideal: 30 }
                },
                audio: false
            });

            this.videoElement.srcObject = stream;
            this.videoElement.play();

            await new Promise((resolve, reject) => {
                this.videoElement.onloadedmetadata = () => resolve();
                setTimeout(() => reject(new Error('Video loading timeout')), 10000);
            });

            this.createCanvas();

            this.camera = new Camera(this.videoElement, {
                onFrame: async () => {
                    if (this.pose && this.isInitialized) {
                        try {
                            await this.pose.send({ image: this.videoElement });
                        } catch (error) {
                            console.error('Frame processing error:', error);
                        }
                    }
                },
                width: 640,
                height: 480
            });

            await this.camera.start();
            console.log('Camera started successfully');

            return true;

        } catch (error) {
            console.error('Camera start failed:', error);
            this.stopCamera();
            throw error;
        }
    }

    createCanvas() {
        this.canvasElement = document.createElement('canvas');
        this.canvasElement.style.position = 'absolute';
        this.canvasElement.style.top = '0';
        this.canvasElement.style.left = '0';
        this.canvasElement.style.zIndex = '1';
        this.canvasElement.width = 640;
        this.canvasElement.height = 480;

        this.canvasCtx = this.canvasElement.getContext('2d');

        const videoContainer = this.videoElement.parentElement;
        videoContainer.style.position = 'relative';
        videoContainer.appendChild(this.canvasElement);
    }

    onResults(results) {
        this.frameCount++;

        if (this.canvasCtx) {
            this.canvasCtx.save();
            this.canvasCtx.clearRect(0, 0, this.canvasElement.width, this.canvasElement.height);
        }

        if (results.poseLandmarks) {
            // Draw skeleton
            if (this.canvasCtx) {
                this.drawLandmarks(results.poseLandmarks);
            }

            // Check pose quality and distance
            const quality = this.validatePoseQuality(results.poseLandmarks);
            this.checkDistance(results.poseLandmarks);

            if (!this.calibrationComplete) {
                this.performCalibration(results.poseLandmarks, quality);
            } else {
                this.calculateMeasurements(results.poseLandmarks);
            }
        } else {
            this.updatePoseIndicator('Please position yourself in the frame', 'waiting');
            this.updateDistanceIndicator(0, 'No body detected');
        }

        if (this.canvasCtx) {
            this.canvasCtx.restore();
        }
    }

    checkDistance(landmarks) {
        const nose = landmarks[this.LANDMARKS.nose];
        const leftAnkle = landmarks[this.LANDMARKS.leftAnkle];
        const rightAnkle = landmarks[this.LANDMARKS.rightAnkle];

        if (!nose || !leftAnkle || !rightAnkle) return;

        // Calculate body height in frame (normalized 0-1)
        const avgAnkleY = (leftAnkle.y + rightAnkle.y) / 2;
        const bodyHeightRatio = avgAnkleY - nose.y;

        // Determine distance status
        let distancePercent = (bodyHeightRatio / this.optimalBodyHeightRatio) * 100;
        distancePercent = Math.min(100, Math.max(0, distancePercent));

        if (bodyHeightRatio < 0.5) {
            this.distanceStatus = 'too_far';
            this.updateDistanceIndicator(distancePercent, 'Move closer to camera');
        } else if (bodyHeightRatio > 0.9) {
            this.distanceStatus = 'too_close';
            this.updateDistanceIndicator(distancePercent, 'Move further from camera');
        } else {
            this.distanceStatus = 'good';
            this.updateDistanceIndicator(distancePercent, 'Distance is good!');
        }
    }

    updateDistanceIndicator(percent, text) {
        const distanceFill = document.getElementById('distance-fill');
        const distanceText = document.getElementById('distance-text');

        if (distanceFill) {
            distanceFill.style.width = percent + '%';
            distanceFill.className = 'distance-fill';
            if (this.distanceStatus === 'good') {
                distanceFill.classList.add('good');
            } else {
                distanceFill.classList.add(this.distanceStatus === 'too_close' ? 'too-close' : 'too-far');
            }
        }

        if (distanceText) {
            distanceText.textContent = text;
            distanceText.style.color = this.distanceStatus === 'good' ? '#28a745' : '#dc3545';
        }
    }

    performCalibration(landmarks, quality) {
        if (quality.visibility < 0.7 || quality.completeness < 0.8) {
            this.updatePoseIndicator('Please stand facing the camera, full body visible', 'waiting');
            return;
        }

        if (this.distanceStatus !== 'good') {
            return; // Wait for good distance
        }

        // Calculate body height in pixels
        const nose = landmarks[this.LANDMARKS.nose];
        const leftAnkle = landmarks[this.LANDMARKS.leftAnkle];
        const rightAnkle = landmarks[this.LANDMARKS.rightAnkle];

        const bodyHeightNorm = ((leftAnkle.y + rightAnkle.y) / 2) - nose.y;
        const bodyHeightPixels = bodyHeightNorm * 480; // Convert to pixels

        this.calibrationFrames.push(bodyHeightPixels);

        const requiredFrames = 10;

        if (this.calibrationFrames.length >= requiredFrames) {
            // Get stable median value
            const sorted = [...this.calibrationFrames].sort((a, b) => a - b);
            this.detectedBodyHeightPixels = sorted[Math.floor(sorted.length / 2)];

            // Calculate pixels per inch using known user height
            // Body height from nose to ankle is approximately 90% of total height
            const noseToAnkleInches = this.userHeightInches * 0.90;
            this.pixelsPerInch = this.detectedBodyHeightPixels / noseToAnkleInches;

            this.calibrationComplete = true;
            this.isCalibrated = true;

            console.log('Calibration complete:');
            console.log(`  User height: ${this.userHeightInches} inches`);
            console.log(`  Detected body pixels: ${this.detectedBodyHeightPixels.toFixed(1)}`);
            console.log(`  Pixels per inch: ${this.pixelsPerInch.toFixed(2)}`);

            this.updatePoseIndicator('Calibrated! Measuring... Click Capture when ready.', 'success');

            // Enable capture button
            const captureBtn = document.getElementById('capture-measurement-btn');
            if (captureBtn) {
                captureBtn.style.background = 'linear-gradient(135deg, #28a745, #20c997)';
                captureBtn.innerHTML = '<i class="fas fa-check"></i> Capture Measurements';
                captureBtn.disabled = false;
            }

        } else {
            this.updatePoseIndicator(`Calibrating... ${this.calibrationFrames.length}/${requiredFrames} (Stay still)`, 'waiting');
        }
    }

    calculateMeasurements(landmarks) {
        try {
            if (!this.calibrationComplete || !this.pixelsPerInch) return;

            const measurements = {};

            // Get landmarks
            const nose = landmarks[this.LANDMARKS.nose];
            const leftShoulder = landmarks[this.LANDMARKS.leftShoulder];
            const rightShoulder = landmarks[this.LANDMARKS.rightShoulder];
            const leftHip = landmarks[this.LANDMARKS.leftHip];
            const rightHip = landmarks[this.LANDMARKS.rightHip];
            const leftWrist = landmarks[this.LANDMARKS.leftWrist];
            const rightWrist = landmarks[this.LANDMARKS.rightWrist];
            const leftKnee = landmarks[this.LANDMARKS.leftKnee];
            const rightKnee = landmarks[this.LANDMARKS.rightKnee];
            const leftAnkle = landmarks[this.LANDMARKS.leftAnkle];
            const rightAnkle = landmarks[this.LANDMARKS.rightAnkle];

            // 1. SHOULDER WIDTH (direct measurement)
            if (this.isLandmarkValid(leftShoulder) && this.isLandmarkValid(rightShoulder)) {
                const shoulderPixels = this.getDistancePixels(leftShoulder, rightShoulder);
                const shoulderInches = shoulderPixels / this.pixelsPerInch;

                measurements.shoulder = {
                    value: this.smoothAndValidate('shoulder', shoulderInches),
                    confidence: Math.min(leftShoulder.visibility, rightShoulder.visibility)
                };
            }

            // 2. CHEST CIRCUMFERENCE (derived from shoulder using body ratio)
            if (measurements.shoulder) {
                const chestCircumference = measurements.shoulder.value * this.BODY_RATIOS.chestToShoulder;
                measurements.chest = {
                    value: this.smoothAndValidate('chest', chestCircumference),
                    confidence: measurements.shoulder.confidence * 0.85
                };
            }

            // 3. HIP WIDTH & CIRCUMFERENCE
            if (this.isLandmarkValid(leftHip) && this.isLandmarkValid(rightHip)) {
                const hipWidthPixels = this.getDistancePixels(leftHip, rightHip);
                const hipWidthInches = hipWidthPixels / this.pixelsPerInch;
                const hipCircumference = hipWidthInches * this.BODY_RATIOS.hipMultiplier;

                measurements.hip = {
                    value: this.smoothAndValidate('hip', hipCircumference),
                    confidence: Math.min(leftHip.visibility, rightHip.visibility)
                };
            }

            // 4. WAIST CIRCUMFERENCE (derived from hip)
            if (measurements.hip) {
                const waistCircumference = measurements.hip.value * this.BODY_RATIOS.waistToHip;
                measurements.waist = {
                    value: this.smoothAndValidate('waist', waistCircumference),
                    confidence: measurements.hip.confidence * 0.85
                };
            }

            // 5. NECK CIRCUMFERENCE (derived from shoulder)
            if (measurements.shoulder) {
                const neckCircumference = measurements.shoulder.value * this.BODY_RATIOS.neckToShoulder;
                measurements.neck = {
                    value: this.smoothAndValidate('neck', neckCircumference),
                    confidence: measurements.shoulder.confidence * 0.8
                };
            }

            // 6. SLEEVE LENGTH (shoulder to wrist - direct measurement)
            const sleeve = this.measureSleeve(leftShoulder, leftWrist, rightShoulder, rightWrist);
            if (sleeve) {
                measurements.sleeve_length = sleeve;
            }

            // 7. KAMEEZ LENGTH (shoulder to knee)
            if (this.isLandmarkValid(leftShoulder) && this.isLandmarkValid(leftKnee)) {
                const kameezPixels = this.getDistancePixels(leftShoulder, leftKnee);
                const kameezInches = kameezPixels / this.pixelsPerInch;

                measurements.kameez_length = {
                    value: this.smoothAndValidate('kameez_length', kameezInches),
                    confidence: Math.min(leftShoulder.visibility, leftKnee.visibility)
                };
            }

            // 8. TROUSER LENGTH (hip to ankle)
            if (this.isLandmarkValid(leftHip) && this.isLandmarkValid(leftAnkle)) {
                const trouserPixels = this.getDistancePixels(leftHip, leftAnkle);
                const trouserInches = trouserPixels / this.pixelsPerInch;

                measurements.trouser_length = {
                    value: this.smoothAndValidate('trouser_length', trouserInches),
                    confidence: Math.min(leftHip.visibility, leftAnkle.visibility)
                };
            }

            this.currentMeasurements = measurements;

        } catch (error) {
            console.error('Measurement calculation error:', error);
        }
    }

    measureSleeve(leftShoulder, leftWrist, rightShoulder, rightWrist) {
        // Use the arm with better visibility
        let sleevePixels = 0;
        let confidence = 0;

        if (this.isLandmarkValid(leftShoulder) && this.isLandmarkValid(leftWrist)) {
            const leftSleeve = this.getDistancePixels(leftShoulder, leftWrist);
            const leftConf = Math.min(leftShoulder.visibility, leftWrist.visibility);

            if (leftConf > confidence) {
                sleevePixels = leftSleeve;
                confidence = leftConf;
            }
        }

        if (this.isLandmarkValid(rightShoulder) && this.isLandmarkValid(rightWrist)) {
            const rightSleeve = this.getDistancePixels(rightShoulder, rightWrist);
            const rightConf = Math.min(rightShoulder.visibility, rightWrist.visibility);

            if (rightConf > confidence) {
                sleevePixels = rightSleeve;
                confidence = rightConf;
            }
        }

        if (sleevePixels > 0 && confidence > 0.6) {
            const sleeveInches = sleevePixels / this.pixelsPerInch;
            return {
                value: this.smoothAndValidate('sleeve_length', sleeveInches),
                confidence: confidence
            };
        }

        return null;
    }

    isLandmarkValid(landmark) {
        return landmark && landmark.visibility > 0.6;
    }

    getDistancePixels(point1, point2) {
        const dx = (point1.x - point2.x) * 640; // Convert to pixels
        const dy = (point1.y - point2.y) * 480;
        return Math.sqrt(dx * dx + dy * dy);
    }

    smoothAndValidate(measurementType, rawValue) {
        if (!this.measurementBuffers.has(measurementType)) {
            this.measurementBuffers.set(measurementType, []);
        }

        const buffer = this.measurementBuffers.get(measurementType);
        buffer.push(rawValue);

        if (buffer.length > this.bufferSize) {
            buffer.shift();
        }

        // Weighted average (more weight to recent values)
        let weightedSum = 0;
        let totalWeight = 0;

        buffer.forEach((value, index) => {
            const weight = Math.pow(0.8, buffer.length - 1 - index);
            weightedSum += value * weight;
            totalWeight += weight;
        });

        let smoothedValue = weightedSum / totalWeight;

        // Validate against ranges
        const range = this.MEASUREMENT_RANGES[measurementType];
        if (range) {
            smoothedValue = Math.max(range.min, Math.min(range.max, smoothedValue));
        }

        return parseFloat(smoothedValue.toFixed(1));
    }

    validatePoseQuality(landmarks) {
        const criticalPoints = [
            this.LANDMARKS.nose,
            this.LANDMARKS.leftShoulder,
            this.LANDMARKS.rightShoulder,
            this.LANDMARKS.leftHip,
            this.LANDMARKS.rightHip,
            this.LANDMARKS.leftAnkle,
            this.LANDMARKS.rightAnkle
        ];

        let visiblePoints = 0;
        let totalVisibility = 0;

        criticalPoints.forEach(index => {
            if (landmarks[index] && landmarks[index].visibility > 0.5) {
                visiblePoints++;
                totalVisibility += landmarks[index].visibility;
            }
        });

        const completeness = visiblePoints / criticalPoints.length;
        const avgVisibility = visiblePoints > 0 ? totalVisibility / visiblePoints : 0;

        return {
            visibility: avgVisibility,
            completeness: completeness,
            isGoodPose: completeness >= 0.8 && avgVisibility >= 0.7
        };
    }

    updatePoseIndicator(message, type = 'info') {
        const indicator = document.getElementById('pose-indicator');
        if (indicator) {
            indicator.textContent = message;
            indicator.className = `pose-indicator ${type}`;

            switch (type) {
                case 'success':
                    indicator.style.borderLeft = '4px solid #28a745';
                    indicator.style.background = 'linear-gradient(135deg, #d4edda, #c3e6cb)';
                    indicator.style.color = '#155724';
                    break;
                case 'waiting':
                    indicator.style.borderLeft = '4px solid #ffc107';
                    indicator.style.background = 'linear-gradient(135deg, #fff3cd, #ffeaa7)';
                    indicator.style.color = '#856404';
                    break;
                case 'error':
                    indicator.style.borderLeft = '4px solid #dc3545';
                    indicator.style.background = 'linear-gradient(135deg, #f8d7da, #f1b0b7)';
                    indicator.style.color = '#721c24';
                    break;
            }
        }
    }

    getCurrentMeasurements() {
        return this.currentMeasurements;
    }

    getSimpleMeasurements() {
        const simple = {};
        for (const [key, data] of Object.entries(this.currentMeasurements)) {
            if (data && data.value) {
                simple[key] = data.value;
            }
        }
        return simple;
    }

    drawLandmarks(landmarks) {
        const connections = [
            [11, 12], // Shoulders
            [11, 13], [13, 15], // Left arm
            [12, 14], [14, 16], // Right arm
            [11, 23], [12, 24], // Torso sides
            [23, 24], // Hips
            [23, 25], [25, 27], // Left leg
            [24, 26], [26, 28]  // Right leg
        ];

        // Draw connections
        this.canvasCtx.strokeStyle = '#00ff00';
        this.canvasCtx.lineWidth = 3;

        connections.forEach(([start, end]) => {
            const startPoint = landmarks[start];
            const endPoint = landmarks[end];

            if (startPoint && endPoint && startPoint.visibility > 0.5 && endPoint.visibility > 0.5) {
                this.canvasCtx.beginPath();
                this.canvasCtx.moveTo(startPoint.x * 640, startPoint.y * 480);
                this.canvasCtx.lineTo(endPoint.x * 640, endPoint.y * 480);
                this.canvasCtx.stroke();
            }
        });

        // Draw key points with confidence-based coloring
        const keyPoints = [0, 11, 12, 13, 14, 15, 16, 23, 24, 25, 26, 27, 28];
        keyPoints.forEach(index => {
            const landmark = landmarks[index];
            if (landmark && landmark.visibility > 0.5) {
                // Color based on confidence
                const color = landmark.visibility > 0.8 ? '#00ff00' : '#ffff00';
                this.canvasCtx.fillStyle = color;
                this.canvasCtx.beginPath();
                this.canvasCtx.arc(landmark.x * 640, landmark.y * 480, 6, 0, 2 * Math.PI);
                this.canvasCtx.fill();

                // Border
                this.canvasCtx.strokeStyle = '#000';
                this.canvasCtx.lineWidth = 1;
                this.canvasCtx.stroke();
            }
        });
    }

    stopCamera() {
        try {
            if (this.camera) {
                this.camera.stop();
                this.camera = null;
            }

            if (this.videoElement && this.videoElement.srcObject) {
                const tracks = this.videoElement.srcObject.getTracks();
                tracks.forEach(track => track.stop());
                this.videoElement.srcObject = null;
            }

            if (this.canvasElement) {
                this.canvasElement.remove();
                this.canvasElement = null;
                this.canvasCtx = null;
            }

            // Reset calibration for next use
            this.calibrationComplete = false;
            this.isCalibrated = false;
            this.calibrationFrames = [];
            this.detectedBodyHeightPixels = null;
            this.pixelsPerInch = null;
            this.currentMeasurements = {};
            this.measurementBuffers.clear();
            this.frameCount = 0;
            this.distanceStatus = 'unknown';

            console.log('Camera stopped and reset');

        } catch (error) {
            console.error('Error stopping camera:', error);
        }
    }

    destroy() {
        this.stopCamera();
        this.pose = null;
        this.isInitialized = false;
    }
}

// Make globally available
window.MediaPipeMeasurement = MediaPipeMeasurement;
