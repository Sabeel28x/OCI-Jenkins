pipeline {
    agent any
    triggers {
        githubPush()
    }
    environment {
        OCI_POOL_ID = 'ocid1.instancepool.oc1.ap-mumbai-1.aaaaaaaazi3z5myovpdtc45qensdkwuqgbyq4lkgeqkz5ish2ij7tfezg6pq'
        OCI_COMPARTMENT_ID = 'ocid1.compartment.oc1..aaaaaaaa3s5mtrcqpxjacf53plx4cvlbw4tttytumicdcojrbe2twmmyib4q'
        REPO_URL = 'https://github.com/Sabeel28x/OCI-Jenkins.git'
        BRANCH = 'main'
        APACHE_DOC_ROOT = '/var/www/html'
        SSH_CREDENTIALS_ID = 'oci'  // Ensure this matches your Jenkins SSH credential ID
    }
    stages {
        stage('Fetch Instance IPs') {
            steps {
                script {
                    echo "Fetching instance list from OCI Instance Pool..."
                    
                    // Ensure Jenkins finds the `oci` CLI
                    def instanceList = sh(script: """
                        export PATH=\$PATH:/var/lib/jenkins/bin
                        export OCI_CLI_AUTH=instance_principal
                        . ~/.bashrc
                        set -e  # Stop script on error
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
                            export PATH=\$PATH:/var/lib/jenkins/bin
                            export OCI_CLI_AUTH=instance_principal
                            . ~/.bashrc
                            set -e
                            oci compute instance list-vnics \
                                --instance-id ${instanceId} \
                                --output json
                        """, returnStdout: true).trim()

                        def vnics = readJSON text: vnicInfo
                        if (vnics.data.size() > 0 && vnics.data[0].containsKey('private-ip')) {
                            def privateIp = vnics.data[0]['private-ip']
                            instanceIps.add(privateIp)
                        } else {
                            echo "Warning: No private IP found for instance ${instanceId}"
                        }
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

        stage('Deploy Code to Instances') {
            steps {
                script {
                    def instanceIps = env.INSTANCE_IPS.split(',')

                    if (instanceIps.length == 0 || instanceIps[0] == '') {
                        error("No valid IPs found. Cannot proceed with deployment.")
                    }

                    // Inject SSH credentials and deploy the code
                    sshagent([SSH_CREDENTIALS_ID]) {
                        instanceIps.each { ip ->
                            echo "Deploying code to instance with IP: ${ip}"

                            sh """
                                ssh -o StrictHostKeyChecking=no opc@${ip} bash -s << 'EOF'
                                    set -e
                                    cd ${APACHE_DOC_ROOT}
                                    sudo rm -rf OCI-Jenkins
                                    sudo git clone ${REPO_URL}
                                    sudo mv OCI-Jenkins/index.php /var/www/html
                                    sudo rm -rf OCI-Jenkins
                                    sudo systemctl restart httpd
                                EOF
                            """
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
