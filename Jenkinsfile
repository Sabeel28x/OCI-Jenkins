pipeline {
    agent any
    triggers {
        githubPush()
    }
    environment {
        OCI_POOL_ID = 'ocid1.instancepool.oc1.ap-mumbai-1.aaaaaaaa4ndkxh7cjzfxssujxpylukjlglgtl2gp7bgw6bcpglbo62gsw5zq'
        OCI_COMPARTMENT_ID = 'ocid1.compartment.oc1..aaaaaaaa3s5mtrcqpxjacf53plx4cvlbw4tttytumicdcojrbe2twmmyib4q'
        REPO_URL = 'https://github.com/Sabeel28x/OCI-Jenkins.git'
        BRANCH = 'main'
        APACHE_DOC_ROOT = '/var/www/html'
        OCI_CLI_PATH = '/var/lib/jenkins/bin'  // Define the OCI CLI path explicitly
    }
    stages {
        stage('Check OCI CLI Availability') {
            steps {
                script {
                    echo "Checking OCI CLI availability..."
                    // Output the environment and check for 'oci' command
                    sh 'echo $PATH'
                    def ociAvailable = sh(script: "which oci", returnStatus: true)
                    if (ociAvailable != 0) {
                        error("OCI CLI is not available in the environment.")
                    }
                    echo "OCI CLI is available."
                }
            }
        }

        stage('Fetch Instance IPs') {
            steps {
                script {
                    echo "Fetching instance list from OCI Instance Pool..."

                    // Add OCI CLI path to the system PATH in the environment
                    withEnv(["PATH+OCI=${OCI_CLI_PATH}:${env.PATH}"]) {
                        // Fetch instance IDs from the instance pool
                        def instanceList = sh(script: """
                            oci compute-management instance-pool list-instances \
                                --instance-pool-id ${OCI_POOL_ID} \
                                --compartment-id ${OCI_COMPARTMENT_ID} \
                                --output json
                        """, returnStdout: true).trim()

                        def instances = readJSON text: instanceList

                        // Extract instance IDs
                        def instanceIds = instances.data.collect { it.id }

                        // Fetch private IPs for each instance
                        def instanceIps = []
                        instanceIds.each { instanceId ->
                            def vnicInfo = sh(script: """
                                oci compute instance list-vnics \
                                    --instance-id ${instanceId} \
                                    --output json
                            """, returnStdout: true).trim()

                            def vnics = readJSON text: vnicInfo
                            def privateIp = vnics.data[0]['private-ip']
                            instanceIps.add(privateIp)
                        }

                        // Ensure IPs were collected
                        if (instanceIps.isEmpty()) {
                            error("No private IPs found. Cannot proceed with deployment.")
                        }

                        echo "Private IPs: ${instanceIps}"
                        env.INSTANCE_IPS = instanceIps.join(',')
                    }
                }
            }
        }

        stage('Deploy Code to Instances') {
            steps {
                script {
                    // Split the IPs into a list
                    def instanceIps = env.INSTANCE_IPS.split(',')

                    // Check if the IPs list is empty or null
                    if (instanceIps.length == 0 || instanceIps[0] == '') {
                        error("No valid IPs found. Cannot proceed with deployment.")
                    }

                    // Inject SSH credentials and deploy the code
                    sshagent(['oci']) {
                        instanceIps.each { ip ->
                            echo "Deploying code to instance with IP: ${ip}"

                            // Ensure that the SSH command runs as expected
                            def result = sh(script: """
                                ssh -o StrictHostKeyChecking=no opc@${ip} '
                                    cd ${APACHE_DOC_ROOT} && \
                                    if [ ! -d "OCI-Jenkins" ]; then \
                                        git clone ${REPO_URL}; \
                                    fi && \
                                    sudo mv OCI-Jenkins/index.php ${APACHE_DOC_ROOT} && \
                                    sudo rm -rf OCI-Jenkins && \
                                    sudo systemctl restart httpd
                                '
                            """, returnStatus: true)

                            if (result != 0) {
                                error("Deployment failed on instance with IP: ${ip}")
                            }
                        }
                    }
                }
            }
        }
    }

    post {
        success {
            echo "Code deployment to all instances completed successfully!"
        }
        failure {
            echo "Code deployment failed."
        }
    }
}
