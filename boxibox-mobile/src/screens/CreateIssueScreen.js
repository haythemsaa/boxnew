import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  KeyboardAvoidingView,
  Platform,
  Alert,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Picker } from '@react-native-picker/picker';
import contractService from '../services/contractService';
import issueService from '../services/issueService';
import Button from '../components/Button';
import Input from '../components/Input';
import Card from '../components/Card';
import Loading from '../components/Loading';
import { COLORS } from '../constants/colors';

const CreateIssueScreen = ({ navigation }) => {
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [contracts, setContracts] = useState([]);
  const [formData, setFormData] = useState({
    contract_id: '',
    type: 'maintenance',
    subject: '',
    description: '',
    priority: 'medium',
  });
  const [errors, setErrors] = useState({});

  useEffect(() => {
    loadContracts();
  }, []);

  const loadContracts = async () => {
    try {
      const data = await contractService.getContracts();
      const activeContracts = (data.contracts || data).filter((c) => c.status === 'active');
      setContracts(activeContracts);

      if (activeContracts.length > 0) {
        setFormData((prev) => ({ ...prev, contract_id: activeContracts[0].id.toString() }));
      }
    } catch (error) {
      console.error('Error loading contracts:', error);
      Alert.alert('Erreur', 'Impossible de charger les contrats');
    } finally {
      setLoading(false);
    }
  };

  const handleChange = (field, value) => {
    setFormData({ ...formData, [field]: value });
    if (errors[field]) {
      setErrors({ ...errors, [field]: undefined });
    }
  };

  const handleSubmit = async () => {
    setErrors({});

    if (!formData.contract_id || !formData.subject || !formData.description) {
      Alert.alert('Erreur', 'Veuillez remplir tous les champs requis');
      return;
    }

    setSubmitting(true);

    try {
      const data = {
        ...formData,
        contract_id: parseInt(formData.contract_id),
      };

      await issueService.createIssue(data);

      Alert.alert('Succès', 'Signalement créé avec succès', [
        {
          text: 'OK',
          onPress: () => navigation.goBack(),
        },
      ]);
    } catch (error) {
      console.error('Error creating issue:', error);
      setErrors(error.errors || {});
      Alert.alert('Erreur', error.message || 'Impossible de créer le signalement');
    } finally {
      setSubmitting(false);
    }
  };

  if (loading) {
    return <Loading />;
  }

  if (contracts.length === 0) {
    return (
      <SafeAreaView style={styles.container}>
        <View style={styles.emptyContainer}>
          <Text style={styles.emptyText}>Vous n'avez aucun contrat actif</Text>
          <Button
            title="Retour"
            onPress={() => navigation.goBack()}
            style={styles.backButton}
          />
        </View>
      </SafeAreaView>
    );
  }

  return (
    <SafeAreaView style={styles.container}>
      <KeyboardAvoidingView
        behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
        style={styles.keyboardView}
      >
        <ScrollView
          style={styles.scrollView}
          keyboardShouldPersistTaps="handled"
        >
          <Card>
            <Text style={styles.sectionTitle}>Contrat concerné</Text>
            <View style={styles.pickerContainer}>
              <Picker
                selectedValue={formData.contract_id}
                onValueChange={(value) => handleChange('contract_id', value)}
                style={styles.picker}
              >
                {contracts.map((contract) => (
                  <Picker.Item
                    key={contract.id}
                    label={`${contract.contract_number} - Box ${contract.box?.number || ''}`}
                    value={contract.id.toString()}
                  />
                ))}
              </Picker>
            </View>
          </Card>

          <Card>
            <Text style={styles.sectionTitle}>Type de signalement</Text>
            <View style={styles.pickerContainer}>
              <Picker
                selectedValue={formData.type}
                onValueChange={(value) => handleChange('type', value)}
                style={styles.picker}
              >
                <Picker.Item label="Accès" value="access" />
                <Picker.Item label="Maintenance" value="maintenance" />
                <Picker.Item label="Facturation" value="billing" />
                <Picker.Item label="Sécurité" value="security" />
                <Picker.Item label="Autre" value="other" />
              </Picker>
            </View>
          </Card>

          <Card>
            <Text style={styles.sectionTitle}>Priorité</Text>
            <View style={styles.pickerContainer}>
              <Picker
                selectedValue={formData.priority}
                onValueChange={(value) => handleChange('priority', value)}
                style={styles.picker}
              >
                <Picker.Item label="Basse" value="low" />
                <Picker.Item label="Moyenne" value="medium" />
                <Picker.Item label="Haute" value="high" />
                <Picker.Item label="Urgente" value="urgent" />
              </Picker>
            </View>
          </Card>

          <Card>
            <Input
              label="Sujet *"
              value={formData.subject}
              onChangeText={(value) => handleChange('subject', value)}
              placeholder="Résumé du problème"
              error={errors.subject?.[0]}
            />

            <Input
              label="Description *"
              value={formData.description}
              onChangeText={(value) => handleChange('description', value)}
              placeholder="Décrivez le problème en détail..."
              multiline
              numberOfLines={6}
              style={styles.textArea}
              error={errors.description?.[0]}
            />
          </Card>

          <View style={styles.actions}>
            <Button
              title="Créer le signalement"
              onPress={handleSubmit}
              loading={submitting}
            />
            <Button
              title="Annuler"
              onPress={() => navigation.goBack()}
              variant="outline"
              style={styles.cancelButton}
            />
          </View>
        </ScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: COLORS.background,
  },
  keyboardView: {
    flex: 1,
  },
  scrollView: {
    flex: 1,
    padding: 16,
  },
  sectionTitle: {
    fontSize: 16,
    fontWeight: '600',
    color: COLORS.text.primary,
    marginBottom: 12,
  },
  pickerContainer: {
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: 8,
    backgroundColor: COLORS.white,
  },
  picker: {
    height: 50,
  },
  textArea: {
    minHeight: 120,
    textAlignVertical: 'top',
  },
  actions: {
    marginBottom: 24,
  },
  cancelButton: {
    marginTop: 12,
  },
  emptyContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 24,
  },
  emptyText: {
    fontSize: 16,
    color: COLORS.text.secondary,
    textAlign: 'center',
    marginBottom: 24,
  },
  backButton: {
    width: 200,
  },
});

export default CreateIssueScreen;
